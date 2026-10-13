<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Wyszukiwarka panelu (Ctrl+K): tytuły, treść z fragmentem, sekcje i zakładki ustawień, uprawnienia. */
class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function search(User $user, string $q): array
    {
        return $this->actingAs($user)->getJson(route('admin.search', ['q' => $q]))->assertOk()->json('results');
    }

    public function test_finds_titles_and_body_with_snippet(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Page::create(['title' => 'Kontakt z biurem', 'slug' => 'kontakt-biuro', 'type' => 'standard', 'is_published' => true]);
        Page::create(['title' => 'Szkolenia', 'slug' => 'szkolenia', 'type' => 'standard', 'is_published' => true,
            'content' => '<p>Zapraszamy na warsztaty z audiodeskrypcji dla nauczycieli.</p>']);

        $titles = collect($this->search($admin, 'biurem'))->pluck('title');
        $this->assertContains('Kontakt z biurem', $titles);

        $hit = collect($this->search($admin, 'audiodeskrypcji'))->firstWhere('title', 'Szkolenia');
        $this->assertNotNull($hit, 'Strona znaleziona po treści.');
        $this->assertStringContainsString('audiodeskrypcji', $hit['secondary']);
        $this->assertStringNotContainsString('<p>', $hit['secondary']);
    }

    public function test_finds_menu_sections_and_settings_tabs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $sections = collect($this->search($admin, "Logowanie"))->where("label", "Sekcja");
        $this->assertTrue($sections->contains(fn ($r) => str_contains($r['url'], 'tab=login')), 'Zakładka ustawień „Logowanie”.');
        $this->assertTrue(collect($this->search($admin, 'Multimedia'))->contains('label', 'Sekcja'));
    }

    public function test_respects_module_permissions(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_PR_EDITOR]);
        Page::create(['title' => 'Tajna strona xyz', 'slug' => 'tajna', 'type' => 'standard', 'is_published' => true]);
        News::create(['title' => 'Aktualność xyz', 'slug' => 'akt-xyz', 'content' => 'x', 'is_published' => true]);

        $labels = collect($this->search($editor, 'xyz'))->pluck('label')->unique()->values()->all();
        if (! $editor->canAccessModule('pages')) {
            $this->assertNotContains('Strona', $labels);
        }
        if ($editor->canAccessModule('news')) {
            $this->assertContains('Aktualność', $labels);
        }
    }
}
