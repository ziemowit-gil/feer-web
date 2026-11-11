<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pasek panelu na stronie publicznej (partials/site-admin-bar): zalogowana redakcja widzi u góry ten sam pasek co w panelu
 * w stylu Metro, z okienkiem „Edytuj” zależnym od strony; dawny pływający panel z kluczem i rozsiane skróty do panelu znikają.
 */
class SiteAdminBarTest extends TestCase
{
    use RefreshDatabase;

    private function page(): Page
    {
        return Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Treść</p>']);
    }

    public function test_gosc_nie_widzi_paska(): void
    {
        $this->page();

        $this->get('/o-nas')->assertOk()
            ->assertDontSee('data-site-admin-bar', false)
            ->assertDontSee('id="mt-nav"', false)
            ->assertDontSee('data-footer-edit', false);
    }

    public function test_administrator_widzi_pasek_metro_z_edycja_strony(): void
    {
        $page = $this->page();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get('/o-nas')->assertOk()->getContent();

        // Pasek na samej górze (zaraz za „Przejdź do treści”), ten sam co w panelu: logo, witryna, grupy, Edytuj, Skróty, konto.
        $this->assertMatchesRegularExpression('~Przejdź do treści\s*</a>.*?<div class="admin-metro site-admin-bar" data-site-admin-bar>\s*<nav id="mt-nav"~s', $html);
        $this->assertStringContainsString('id="mt-btn-witryna"', $html);
        $this->assertStringContainsString('class="mt-groups"', $html);
        $this->assertStringContainsString('id="mt-btn-skroty"', $html);
        $this->assertStringContainsString("Alpine.data('navQuick'", $html);
        $this->assertStringContainsString('.site-admin-bar .mt-nav { position: relative;', $html);

        // Edytuj: ta strona (na stronie, w edytorze blokowym, w panelu) i stopka.
        $this->assertMatchesRegularExpression('~id="mt-panel-edytuj".*?Ta strona.*?site-admin-edit~s', $html);
        $this->assertStringContainsString('href="'.route('admin.podstrony.edit', $page).'"', $html);
        $this->assertStringContainsString('<span>Edytuj w panelu</span>', $html);
        $this->assertMatchesRegularExpression('~href="[^"]*#gen-footer-h"\s+data-footer-edit~', $html);

        // Pasek edycji na stronie czeka na „Edytuj na stronie” (bez pływającego przycisku).
        $this->assertStringContainsString('data-inline-edit', $html);
        $this->assertStringContainsString('@site-admin-edit.window="if (! editMode) toggleEdit()"', $html);

        // Dawny pływający panel z kluczem zniknął, w menu konta nie ma „Wróć do strony”.
        $this->assertStringNotContainsString('Otwórz panel administracyjny', $html);
        $this->assertStringNotContainsString('aria-label="Zamknij panel"', $html);
        $this->assertStringNotContainsString('Wróć do strony', $html);
    }

    public function test_edytor_tresci_widzi_pasek_bez_ustawien_witryny(): void
    {
        $this->page();
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)->get('/o-nas')->assertOk()
            ->assertSee('data-site-admin-bar', false)
            ->assertSee('<span>Edytuj w panelu</span>', false)
            ->assertDontSee('data-footer-edit', false);
    }

    public function test_aktualnosc_i_strona_glowna_maja_swoje_pozycje(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $news = News::create(['title' => 'Nowość', 'slug' => 'nowosc', 'is_published' => true, 'published_at' => now(), 'content' => '<p>Tekst</p>']);

        $this->actingAs($admin)->get(route('news.show', $news))->assertOk()
            ->assertSee('Ta aktualność')
            ->assertSee('href="'.route('admin.newsy.edit', $news).'"', false)
            ->assertSee('@site-admin-edit.window="collapsed = false; if (! editMode) toggleEdit()"', false);

        $this->actingAs($admin)->get('/')->assertOk()
            ->assertSee('Strona główna')
            ->assertSee('<span>Edytuj stronę główną</span>', false)
            ->assertSee('<span>Zarządzaj aktualnościami</span>', false);
    }
}
