<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Szablon „test3” (portal miejski): nagłówek z belkami kategorii, strona główna z sliderem, kaflami, aktualnościami i liczbami. */
class TemplateTest3Test extends TestCase
{
    use RefreshDatabase;

    private function resetSettingsCache(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function useTest3(array $extra = []): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'test3'] + $extra)->save();
        $this->resetSettingsCache();
    }

    private function nav(string $label, array $extra = []): NavItem
    {
        static $order = 0;

        return NavItem::create($extra + ['label' => $label, 'url' => '#', 'location' => 'main', 'type' => 'dropdown', 'is_active' => true, 'order' => ++$order]);
    }

    public function test_template_is_offered_in_settings(): void
    {
        $this->assertArrayHasKey('test3', SiteSetting::SITE_TEMPLATES);
    }

    public function test_menu_dzieli_sie_na_kolorowe_belki_kategorii_i_szybkie_linki(): void
    {
        $this->useTest3();

        $a = $this->nav('Dla mieszkańca');
        NavItem::create(['label' => 'Samorząd', 'url' => '/samorzad', 'location' => 'main', 'type' => 'link', 'parent_id' => $a->id, 'is_active' => true, 'order' => 1]);
        $b = $this->nav('Biznes i rozwój', ['accent_color' => '#00aa55']);
        NavItem::create(['label' => 'Zamówienia', 'url' => '/zamowienia', 'location' => 'main', 'type' => 'link', 'parent_id' => $b->id, 'is_active' => true, 'order' => 1]);
        $this->nav('Geoportal', ['type' => 'link', 'url' => '/geoportal']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="z3 z3-header"', $html);
        $this->assertStringContainsString('Dla mieszkańca', $html);
        $this->assertStringContainsString('Samorząd', $html);
        $this->assertStringContainsString('--z3-c: #d32f2f', $html, 'pierwsza belka w domyślnej palecie (czerwona)');
        $this->assertStringContainsString('--z3-c: #00aa55', $html, 'kolor pozycji z menu ma pierwszeństwo przed paletą');
        $this->assertStringContainsString('aria-label="Podmenu: Dla mieszkańca"', $html);
        $this->assertStringContainsString('<nav aria-label="Szybkie linki"', $html);
        $this->assertMatchesRegularExpression('/<nav aria-label="Szybkie linki".*Geoportal/s', $html, 'pozycja bez podmenu to szybki link');
        $this->assertSame(2, substr_count($html, 'class="z3-cat"'), 'belki tylko dla pozycji z podmenu');
    }

    public function test_pasek_gorny_ma_narzedzia_dostepnosci_i_szybkie_linki_modulow(): void
    {
        $this->useTest3();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-a11y-font="up"', $html);
        $this->assertStringContainsString('data-a11y-contrast="contrast"', $html);
        $this->assertStringContainsString('id="a11y-panel"', $html, 'pełny panel ułatwień pod paskiem');
        $this->assertStringContainsString('aria-label="Deklaracja dostępności"', $html);
        $this->assertStringContainsString('class="z3-util-links"', $html);
        $this->assertStringContainsString('class="z3-search"', $html);
    }

    public function test_strona_glowna_ma_kafle_skrotow_i_aktualnosci_z_filtrem_kategorii(): void
    {
        $this->useTest3();
        foreach (range(1, 3) as $i) {
            QuickAction::create(['label' => "Skrót $i", 'url' => "/s$i", 'icon' => 'fa-solid fa-car', 'order' => $i]);
        }
        QuickAction::create(['label' => 'Kalendarz', 'url' => '/kalendarz', 'icon' => 'fa-solid fa-calendar', 'order' => 4, 'color' => '#99cc00']);

        $inw = NewsCategory::create(['name' => 'Inwestycje', 'slug' => 'inwestycje']);
        $sport = NewsCategory::create(['name' => 'Sport', 'slug' => 'sport']);
        foreach ([[1, $inw], [2, $sport], [3, $inw], [4, $sport]] as [$i, $cat]) {
            News::create(['title' => "Wpis $i", 'slug' => "wpis-$i", 'is_published' => true, 'published_at' => now()->subDays($i), 'news_category_id' => $cat->id, 'excerpt' => "Zajawka $i"]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Skrót 1', $html);
        $this->assertStringContainsString('z3-tile z3-tile--c', $html);
        $this->assertStringContainsString('background: #99cc00', $html, 'kafel z własnym kolorem');
        $this->assertStringContainsString('Wpis 4', $html, 'w DOM jest dziewięć najnowszych; trzy pokazuje filtr po stronie przeglądarki');
        $this->assertStringContainsString('Zajawka 1', $html);
        $this->assertStringContainsString('Kategorie aktualności', $html);
        $this->assertStringContainsString('>Inwestycje</a>', $html);
        $this->assertStringContainsString('>Sport</a>', $html);
        $this->assertStringContainsString('kategoria=sport', $html, 'bez JS pigułka to link do listy z filtrem');
        $this->assertStringContainsString('Wszystkie aktualności', $html);
    }

    public function test_pigulki_kategorii_nie_pojawiaja_sie_przy_jednej_kategorii(): void
    {
        $this->useTest3();
        $cat = NewsCategory::create(['name' => 'Inwestycje', 'slug' => 'inwestycje']);
        News::create(['title' => 'Jedyny wpis', 'slug' => 'jedyny', 'is_published' => true, 'published_at' => now(), 'news_category_id' => $cat->id]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Jedyny wpis', $html);
        $this->assertStringNotContainsString('Kategorie aktualności', $html);
    }

    public function test_sekcja_aktualnosci_respektuje_przelacznik_na_stronie_glownej(): void
    {
        $this->useTest3(['homepage_sections_hidden' => ['news']]);
        News::create(['title' => 'Ukryty wpis', 'slug' => 'ukryty', 'is_published' => true, 'published_at' => now()]);

        $this->get('/')->assertOk()->assertDontSee('z3-news-h', false)->assertDontSee('Ukryty wpis');
    }

    public function test_pas_liczb_pokazuje_dane_ze_strony_o_organizacji_a_bez_danych_go_nie_ma(): void
    {
        $this->useTest3(['ngo_3_stats' => null]);

        $this->assertStringNotContainsString('W liczbach', $this->get('/')->assertOk()->getContent(), 'domyślnych przykładowych liczb nie pokazujemy');

        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true, 'content' => '',
            'about_stats' => [['value' => '108 072', 'label' => 'Liczba ludności'], ['value' => '85,7', 'label' => 'Powierzchnia w km²']]]);
        $this->useTest3(['tagline' => 'Nasze miasto w liczbach']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('108 072', $html);
        $this->assertStringContainsString('Liczba ludności', $html);
        $this->assertStringContainsString('Nasze miasto w liczbach', $html);
        $this->assertStringContainsString('Zobacz więcej', $html);
        $this->assertStringContainsString('href="http://localhost:8000/o-nas"', $html);
    }

    public function test_galeria_i_partnerzy_bez_danych_sie_nie_pokazuja(): void
    {
        $this->useTest3();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('z3-gallery-h', $html);
        $this->assertStringNotContainsString('aria-label="Partnerzy"', $html);
    }

    public function test_slider_ma_przycisk_wstrzymania_tylko_przy_wielu_slajdach(): void
    {
        $this->useTest3();
        $slide = fn (string $title) => (object) ['title' => $title, 'image_url' => null, 'button_url' => '/x', 'image_alt' => ''];
        $render = fn ($slides) => $this->view('templates.test3.partials.home.hero', ['slides' => $slides, 'quickLinks' => collect(), 'siteSettings' => SiteSetting::current()]);

        $render(collect([$slide('Pierwszy'), $slide('Drugi')]))
            ->assertSee('Wstrzymaj automatyczną zmianę slajdów')->assertSee('Poprzedni slajd')->assertSee('Pierwszy')->assertSee('Drugi');
        $render(collect([$slide('Jedyny')]))
            ->assertDontSee('Wstrzymaj automatyczną zmianę slajdów')->assertSee('Jedyny');
    }

    public function test_slajd_misji_bez_zdjecia_i_tytulu_nie_psuje_strony(): void
    {
        $this->useTest3();
        $slides = collect([(object) ['mission_text' => 'Nasza misja', 'logo_url' => null, 'site_name' => 'Miasto', 'mission_bg' => 'brand']]);

        $this->view('templates.test3.partials.home.hero', ['slides' => $slides, 'quickLinks' => collect(), 'siteSettings' => SiteSetting::current()])
            ->assertSee('Nasza misja');
    }

    public function test_podstrona_ma_naglowek_stopke_i_klase_body(): void
    {
        $this->useTest3(['contact_phone' => '95 722 00 00']);

        $this->get('/kontakt')->assertOk()
            ->assertSee('z3-header', false)->assertSee('template-test3', false)
            ->assertSee('z3-foot', false)->assertSee('95 722 00 00')->assertSee('data-z3-top', false);
    }

    public function test_inne_szablony_nie_dostaja_stylow_ani_klasy_test3(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'default'])->save();
        $this->resetSettingsCache();

        $this->get('/kontakt')->assertOk()->assertDontSee('template-test3', false)->assertDontSee('z3-header', false);
    }

    public function test_wylaczony_slider_zostawia_same_kafle_skrotow(): void
    {
        $this->useTest3(['homepage_sections_hidden' => ['hero']]);
        QuickAction::create(['label' => 'Dowód osobisty', 'url' => '/dowod', 'icon' => 'fa-solid fa-id-card', 'order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('class="z3-slider"', $html);
        $this->assertStringContainsString('z3-tiles z3-tiles--wide', $html);
        $this->assertStringContainsString('Dowód osobisty', $html);
    }

    public function test_bez_js_belki_i_aktualnosci_zostaja_widoczne_a_pusta_lista_linkow_stopki_znika(): void
    {
        $this->useTest3();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<noscript><style>[x-cloak] { display: block !important; }</style></noscript>', $html);
        $this->assertStringNotContainsString('Przydatne linki', $html, 'bez pozycji menu stopki nie ma pustej listy');

        NavItem::create(['label' => 'Polityka prywatności', 'url' => '/polityka', 'location' => 'footer', 'type' => 'link', 'is_active' => true, 'order' => 1]);
        $this->get('/')->assertOk()->assertSee('Przydatne linki')->assertSee('Polityka prywatności');
    }
}
