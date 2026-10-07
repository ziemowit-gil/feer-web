<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageEditFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_formularz_edycji_strony_ma_naglowek_ze_statusem_typem_i_przycisk_zapisu(): void
    {
        $page = Page::create(['title' => 'Strona testowa', 'slug' => 'strona-testowa', 'type' => 'links_hub', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))
            ->assertOk()
            ->assertSee('Edycja strony')
            ->assertSee('Opublikowana')
            ->assertSee('Strona z kafelkami — metro')
            ->assertSee('form="page-edit-form"', false)
            ->assertSee('id="page-edit-form"', false);
    }

    public function test_typ_szablon_i_styl_nawigacji_wybiera_sie_w_oknie_dialogowym(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-picker', 'type' => 'standard', 'is_published' => true]);

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();

        foreach (['type-picker', 'template-picker', 'nav-style-picker'] as $id) {
            $this->assertStringContainsString($id, $html);
        }
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('data-page-type-select', $html); // natywny select zostaje nośnikiem wartości
        $this->assertStringContainsString('name="side_nav_style"', $html);
    }

    public function test_zakladka_plikow_ma_strefe_upuszczania(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-pliki', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('Kliknij, aby wybrać plik, albo przeciągnij go tutaj');
    }

    public function test_edytor_kafelkow_ma_podglad_paleta_i_segmenty(): void
    {
        $page = Page::create(['title' => 'Siatka', 'slug' => 'siatka-test', 'type' => 'tiles_grid', 'is_published' => true,
            'tiles' => [['label' => 'Panel kursanta', 'url' => '/panel', 'icon' => 'bi-person', 'color' => '#1e6dff', 'cols' => 2, 'strip' => false, 'is_negative' => true]]]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('Podgląd kafelka')
            ->assertSee('Kolory z brandbooka')
            ->assertSee('tiles[0][label]', false)
            ->assertSee('tiles[__INDEX__][label]', false)
            ->assertSee('Negatyw');
    }

    /**
     * Regresja: nadmiarowy </div> w formularzu zamykał przeglądarce <form> przed panelami „Publikacja" i „SEO", więc ich pola
     * nie trafiały do zapisu. Sprawdzamy, że znaczniki wewnątrz formularza zamykają się parami (div z div, form z form).
     */
    public function test_znaczniki_wewnatrz_formularza_edycji_zamykaja_sie_parami(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-dom', 'type' => 'standard', 'is_published' => true]);

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();

        $start = strpos($html, 'id="page-edit-form"');
        $this->assertNotFalse($start);
        $html = substr($html, $start);

        $void = ['input', 'img', 'br', 'hr', 'meta', 'link', 'source', 'col', 'area', 'base', 'embed', 'param', 'track', 'wbr', 'path', 'circle', 'rect'];
        $stack = ['form'];
        $attrStack = ['form'];
        $pos = 0;
        $panelsSeen = [];
        while (preg_match('/<!--.*?-->|<(\/?)([a-zA-Z][a-zA-Z0-9-]*)((?:"[^"]*"|\'[^\']*\'|[^\'">])*)>/s', $html, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            if (str_starts_with($m[0][0], '<!--')) {
                continue;
            }
            [$closing, $tag, $attrs] = [$m[1][0] === '/', strtolower($m[2][0]), $m[3][0]];
            if (! $closing && in_array($tag, ['script', 'style'], true)) {
                $pos = strpos($html, '</'.$tag, $pos) + strlen($tag) + 3;
                continue;
            }
            if (in_array($tag, $void, true) || str_ends_with(trim($attrs), '/')) {
                continue;
            }
            if (! $closing) {
                $stack[] = $tag;
                $attrStack[] = substr($attrs, 0, 90);
                if (preg_match('/data-ftab-panel="([a-z]+)"/', $attrs, $pm)) {
                    $panelsSeen[] = $pm[1];
                    if (count($stack) !== 2) {
                        $this->fail("Panel {$pm[1]} nie jest bezpośrednim dzieckiem formularza; stos: ".implode(' > ', array_map(fn ($t, $a) => $t.'['.substr($a, 0, 50).']', $stack, $attrStack)));
                    }
                }
                continue;
            }
            $top = array_pop($stack);
            $topAttrs = array_pop($attrStack);
            if ($top !== $tag) {
                $this->fail("Niezgodne zamknięcie: </{$tag}> zamyka <{$top} {$topAttrs}> (panele przed błędem: ".implode(',', $panelsSeen).')');
            }
            if ($stack === []) {
                break; // zamknięto </form>
            }
        }

        $this->assertSame(['tresc', 'typ', 'ustawienia', 'seo'], $panelsSeen);
    }
}
