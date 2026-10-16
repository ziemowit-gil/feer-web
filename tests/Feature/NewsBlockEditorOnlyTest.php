<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Z włączonym edytorem blokowym aktualności edytuje się tylko w nim — formularz
 * klasyczny (?osadzony=1) jest oknem „Ustawienia aktualności” wewnątrz edytora.
 */
class NewsBlockEditorOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function news(array $attributes = []): News
    {
        return News::create($attributes + [
            'title' => 'Aktualność', 'slug' => 'aktualnosc', 'content' => '<p>Treść</p>',
            'published_at' => now()->subDay(), 'is_published' => true,
        ]);
    }

    private function disableBlockEditor(): void
    {
        $settings = SiteSetting::current();
        $settings->update(['disabled_modules' => array_values(array_unique(array_merge((array) $settings->disabled_modules, ['block_editor'])))]);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_edit_always_opens_block_editor_even_with_klasyczny(): void
    {
        $news = $this->news();

        $this->actingAs($this->admin())
            ->get(route('admin.newsy.edit', [$news, 'klasyczny' => 1]))
            ->assertRedirect(route('admin.edytor-blokowy.edit', ['news', $news->id]));
    }

    public function test_create_opens_a_draft_in_the_block_editor(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.newsy.create'));

        $news = News::latest('id')->first();
        $this->assertNotNull($news);
        $this->assertFalse($news->is_published);
        $response->assertRedirect(route('admin.edytor-blokowy.edit', ['news', $news->id]));
    }

    public function test_block_editor_links_the_embedded_settings_form(): void
    {
        $news = $this->news();

        $this->actingAs($this->admin())
            ->get(route('admin.edytor-blokowy.edit', ['news', $news->id]))
            ->assertOk()
            ->assertSee('newsForm', false)
            ->assertSee('osadzony=1', false);
    }

    public function test_embedded_form_has_no_title_content_seo_description_or_publication(): void
    {
        $news = $this->news();

        $this->actingAs($this->admin())
            ->get(route('admin.newsy.edit', [$news, 'osadzony' => 1]))
            ->assertOk()
            ->assertSee('name="osadzony"', false)
            ->assertSee('name="slug"', false)
            ->assertSee('name="excerpt"', false)
            ->assertSee('name="news_category_id"', false)
            ->assertDontSee('name="title"', false)
            ->assertDontSee('name="meta_description"', false)
            ->assertDontSee('name="is_published"', false)
            ->assertDontSee('name="published_at"', false)
            ->assertDontSee('id="admin-sidebar"', false);
    }

    public function test_embedded_save_keeps_fields_owned_by_the_block_editor(): void
    {
        $news = $this->news(['meta_description' => 'Opis SEO', 'is_archived' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.newsy.update', $news), [
                'osadzony' => '1',
                'after' => 'stay',
                'slug' => 'nowy-adres',
                'excerpt' => 'Nowa zajawka',
                'post_type' => 'standard',
            ])
            ->assertRedirect(route('admin.newsy.edit', [$news, 'osadzony' => 1]));

        $news->refresh();
        $this->assertSame('nowy-adres', $news->slug);
        $this->assertSame('Nowa zajawka', $news->excerpt);
        $this->assertSame('Aktualność', $news->title);
        $this->assertSame('<p>Treść</p>', $news->content);
        $this->assertSame('Opis SEO', $news->meta_description);
        $this->assertTrue($news->is_published);
        $this->assertTrue($news->is_archived);
    }

    public function test_without_block_editor_the_classic_form_stays(): void
    {
        $this->disableBlockEditor();
        $news = $this->news();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.newsy.edit', $news))->assertOk()->assertSee('name="title"', false);
        $this->actingAs($admin)->get(route('admin.newsy.create'))->assertOk();
        $this->assertSame(1, News::count());
    }
}
