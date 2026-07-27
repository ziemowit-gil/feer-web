<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wyzeruj cache singletonu SiteSetting, aby stan z innych testów nie
        // wyciekał (moduł „pages" i middleware trasy zależą od tych ustawień).
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function aboutPage(): Page
    {
        return Page::create([
            'title' => 'Fundacja FEER',
            'slug' => 'o-nas',
            'type' => 'about',
        ]);
    }

    public function test_editor_shows_the_about_page(): void
    {
        $page = $this->aboutPage();

        $this->actingAs($this->admin())
            ->get(route('admin.os-czasu.edit'))
            ->assertOk()
            ->assertSee('Fundacja FEER')
            ->assertSee('Dodaj wpis');
    }

    public function test_editor_warns_when_no_about_page_exists(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.os-czasu.edit'))
            ->assertOk()
            ->assertSee('Brak strony typu');
    }

    public function test_timeline_is_saved_and_empty_rows_dropped(): void
    {
        $page = $this->aboutPage();

        $this->actingAs($this->admin())
            ->put(route('admin.os-czasu.update', $page), [
                'about_timeline' => [
                    ['year' => '2015', 'text' => 'Powstanie fundacji', 'color' => '#c31432'],
                    ['year' => '', 'text' => '', 'color' => ''], // pusty — pomijany
                    ['year' => '2020', 'text' => 'Nowa siedziba'],
                ],
            ])
            ->assertRedirect(route('admin.os-czasu.edit', ['page' => $page->id]));

        $timeline = $page->fresh()->about_timeline;

        $this->assertCount(2, $timeline);
        $this->assertSame('2015', $timeline[0]['year']);
        $this->assertSame('Nowa siedziba', $timeline[1]['text']);
    }

    public function test_update_rejects_non_about_page(): void
    {
        $plain = Page::create(['title' => 'Zwykła', 'slug' => 'zwykla', 'type' => 'page']);

        $this->actingAs($this->admin())
            ->put(route('admin.os-czasu.update', $plain), ['about_timeline' => []])
            ->assertNotFound();
    }
}
