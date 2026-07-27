<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_legacy_page_renders_header_and_content(): void
    {
        Page::create([
            'title' => 'Nasze korzenie', 'slug' => 'korzenie', 'type' => 'legacy', 'is_published' => true,
            'legacy_name' => 'Stowarzyszenie Dawne', 'legacy_intro' => 'Działało w latach 90.',
            'content' => '<p>Historia działalności.</p>',
        ]);

        $this->get('/korzenie')
            ->assertOk()
            ->assertSee('Kontynuujemy tę działalność')
            ->assertSee('Stowarzyszenie Dawne')
            ->assertSee('Działało w latach 90.')
            ->assertSee('Historia działalności.', false);
    }

    public function test_admin_saves_legacy_fields_and_clears_when_type_changes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Korzenie', 'slug' => 'korzenie', 'type' => 'legacy', 'is_published' => true]);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Korzenie', 'slug' => 'korzenie', 'type' => 'legacy', 'is_published' => '1',
            'parent_id' => '', 'project_id' => '',
            'legacy_name' => 'Fundacja Poprzednik', 'legacy_intro' => 'Wstęp',
        ])->assertRedirect();

        $this->assertSame('Fundacja Poprzednik', $page->fresh()->legacy_name);

        // Zmiana typu na standardowy czyści pola legacy.
        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Korzenie', 'slug' => 'korzenie', 'type' => 'standard', 'is_published' => '1',
            'parent_id' => '', 'project_id' => '',
        ])->assertRedirect();

        $this->assertNull($page->fresh()->legacy_name);
    }
}
