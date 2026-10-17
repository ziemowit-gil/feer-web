<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Przebudowany widok bloku „Shortcode” w edytorze blokowym: po wyborze — rodzaj i nazwa modułu
 * z akcjami „Edytuj” / „Zmień moduł”; bez modułu — wybór z listy z widoczną etykietą, osobna grupa
 * „Albo utwórz nowy” i zwinięte pole „Wpisz kod ręcznie”. JS nie działa w testach Feature, więc
 * sprawdzamy źródło edytora i dane, z których korzysta (lista modułów, podgląd).
 */
class BlockShortcodeViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_widok_bloku_shortcode_ma_podsumowanie_i_wybor_modulu(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        foreach (["'Zmień moduł'", "'Gotowy moduł'", "'Albo utwórz nowy'", "'Wpisz kod ręcznie'", "'Użyj kodu'", "'Anuluj zmianę'", "'Siatka kafelków'", "'Zestaw kafelków'"] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        $this->assertStringContainsString("h('label', { for: uid + '-pick'", $js); // widoczna etykieta listy (WCAG 3.3.2)
        $this->assertStringContainsString("role: 'group', 'aria-labelledby': uid + '-new'", $js);
        $this->assertStringContainsString("'aria-live': 'polite'", $js);
        $this->assertStringNotContainsString("' Moduł (shortcode)'", $js);
        $this->assertStringContainsString('.be-sc-head', file_get_contents(resource_path('css/block-editor.css')));
    }

    public function test_edytor_dostaje_liste_modulow_i_podglad_siatki_kafelkow(): void
    {
        Page::create(['title' => 'Dla kursantów', 'slug' => 'dla-kursantow', 'type' => 'tiles_grid', 'is_published' => true, 'content' => '',
            'tiles' => [['label' => 'Harmonogram', 'url' => '/harmonogram']]]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>x</p>', 'is_published' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Lista modułów w konfiguracji edytora (z niej powstaje nazwa w podsumowaniu bloku).
        $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))
            ->assertOk()
            ->assertSee('dla-kursantow', false);

        // Podgląd opublikowanej siatki z kafelkami nie jest pusty.
        $this->actingAs($admin)->postJson(route('admin.edytor-blokowy.shortcode'), ['code' => '[kafelki:dla-kursantow]'])
            ->assertOk()->assertSee('Harmonogram');

        // Nieistniejąca siatka daje pusty podgląd — edytor pokazuje wtedy podpowiedź dla siatki.
        $html = $this->actingAs($admin)->postJson(route('admin.edytor-blokowy.shortcode'), ['code' => '[kafelki:nie-ma-takiej]'])->assertOk()->json('html');
        $this->assertSame('', trim(strip_tags($html)));
        $this->assertStringContainsString('Siatka kafelków nic nie pokazuje', file_get_contents(resource_path('js/block-editor.js')));
    }
}
