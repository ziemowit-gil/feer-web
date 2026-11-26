<?php

namespace Tests\Feature;

use App\Models\EducationalMaterial;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nowy wygląd strony „Materiały edukacyjne” w szablonie FEER: karty bez etykiet na zdjęciu,
 * typ i Premium jako tekst pod tytułem, cała karta jako jeden link z czytelną nazwą,
 * wyszukiwarka i filtr typu, komunikat z panelu jako jedna linia.
 */
class MaterialsFeerLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useTemplate('feer');
    }

    private function useTemplate(string $template, array $extra = []): void
    {
        $forget = fn () => \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $forget();
        SiteSetting::current()->update(['site_template' => $template] + $extra);
        $forget();
    }

    private function seedMaterials(): void
    {
        EducationalMaterial::create(['title' => 'Webinar Office365', 'description' => 'Nagranie webinaru', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'is_published' => true, 'target_group' => 'ogolny']);
        EducationalMaterial::create(['title' => 'Mini komiks Autyzm', 'description' => 'Komiks w PDF', 'type' => 'pdf', 'is_published' => true, 'target_group' => 'ogolny']);
        EducationalMaterial::create(['title' => 'Dzień dobry, dziękuję', 'description' => 'Film', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'is_published' => true, 'is_premium' => true, 'target_group' => 'ogolny']);
    }

    public function test_karty_bez_etykiet_na_zdjeciu_i_bez_przyciemnienia(): void
    {
        $this->seedMaterials();

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringNotContainsString('absolute left-3 top-3', $html); // stara etykieta typu na zdjęciu
        $this->assertStringNotContainsString('bg-ink px-2.5', $html); // stara etykieta „Premium”
        $this->assertStringNotContainsString('bg-ink/70', $html); // przyciemnienie zablokowanego materiału
        $this->assertStringNotContainsString('fa-lock text-3xl', $html);
        $this->assertStringNotContainsString('text-6xl text-gray-400', $html); // stary szary placeholder PDF
        $this->assertStringContainsString('mat-thumb--doc', $html);
        // Typ jako pierwsza linia karty: kafelek z ikoną (dekoracyjny) + nazwa typu tekstem, bez etykiety z ramką.
        $this->assertMatchesRegularExpression('~<p class="mat-type">\s*<span class="mat-type-icon"><i class="fa-solid fa-video" aria-hidden="true"></i></span>\s*Nagranie wideo\s*</p>~u', $html);
        $this->assertMatchesRegularExpression('/<i class="fa-solid fa-lock" aria-hidden="true"><\/i> Premium<\/span>/u', $html);
    }

    public function test_cala_karta_to_jeden_link_z_czytelna_nazwa(): void
    {
        $this->seedMaterials();

        $html = $this->get('/materialy')->assertOk()->getContent();

        // Nagranie — nowa karta, nazwa linku = tytuł + działanie.
        $this->assertMatchesRegularExpression('~<a href="https://www\.youtube\.com/watch\?v=dQw4w9WgXcQ" class="mat-link"\s+target="_blank" rel="noopener"\s*>Webinar Office365<span class="sr-only"> — obejrzyj nagranie \(otwiera się w nowej karcie\)</span></a>~u', $html);
        // Materiał Premium dla gościa prowadzi do logowania.
        $this->assertMatchesRegularExpression('~<a href="' . preg_quote(route('login'), '~') . '" class="mat-link"\s*>Dzień dobry, dziękuję<span class="sr-only"> — materiał Premium, zaloguj się, aby otworzyć</span></a>~u', $html);
        $this->assertStringContainsString('Zaloguj się, aby otworzyć', $html);
        // Jedno widoczne wezwanie na kartę, dekoracyjne dla czytników (nazwę niesie link).
        $this->assertSame(2, substr_count($html, 'class="mat-cta" aria-hidden="true"')); // PDF bez pliku nie ma działania
        // Podgląd jest dekoracyjny, bez osobnych przycisków „Pobierz PDF” / „Podgląd” na karcie.
        $this->assertStringContainsString('class="mat-thumb mat-thumb--video mat-thumb--video" aria-hidden="true"', $html);
        $this->assertStringNotContainsString('Obejrzyj nagranie<span class="sr-only">', $html);
    }

    public function test_zalogowany_z_dostepem_widzi_otwarte_premium(): void
    {
        $this->seedMaterials();
        $user = User::factory()->create();
        $material = EducationalMaterial::where('is_premium', true)->first();
        \Revoltify\Subscriptionify\Models\Feature::firstOrCreate(['slug' => "material:{$material->id}"], ['name' => 'Materiał', 'type' => 'toggle', 'sort_order' => 101]);
        $user->grantFeature("material:{$material->id}");

        $html = $this->actingAs($user)->get('/materialy')->assertOk()->getContent();

        $this->assertStringContainsString('fa-lock-open', $html);
        $this->assertStringContainsString('Dzień dobry, dziękuję<span class="sr-only"> — obejrzyj nagranie', $html);
    }

    public function test_naglowek_ze_skrotami_typow_i_formularz_filtrow(): void
    {
        $this->seedMaterials();

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertStringContainsString('<h1 id="mat-h1" class="mat-hero-title">Materiały edukacyjne</h1>', $html);
        // Skróty w nagłówku: liczba materiałów każdego typu jako link z filtrem.
        $this->assertMatchesRegularExpression('~<span class="mat-stat-n">3</span> materiały w bazie</a>~u', $html);
        $this->assertStringContainsString('href="' . route('materials.index', ['typ' => 'video']) . '#lista" class="mat-stat mat-stat--video"', $html);
        $this->assertMatchesRegularExpression('~<span class="mat-stat-n">2</span> nagrania</a>~u', $html);
        $this->assertMatchesRegularExpression('~<span class="mat-stat-n">1</span> plik PDF</a>~u', $html);
        // Formularz GET działa bez JavaScriptu; pola z etykietami, typ jako radio w fieldset z legendą.
        $this->assertMatchesRegularExpression('~<form method="GET" action="' . preg_quote(route('materials.index'), '~') . '#lista" role="search" aria-label="Filtry materiałów"~u', $html);
        $this->assertStringContainsString('<label for="material-search" class="mat-label">Szukaj w tytułach i opisach</label>', $html);
        $this->assertStringContainsString('<legend class="mat-label">Typ materiału</legend>', $html);
        $this->assertMatchesRegularExpression('~<input type="radio" name="typ" value="video"\s+data-material-filter="typ"> <span>Nagrania</span> <span class="mat-option-n">2</span>~u', $html);
        $this->assertStringNotContainsString('name="typ" value="scenariusz"', $html); // tylko obecne typy
        $this->assertStringNotContainsString('id="material-group"', $html); // jedna grupa — bez wyboru „Dla kogo”
        $this->assertStringContainsString('<label for="material-sort" class="mat-label">Kolejność</label>', $html);
        $this->assertStringContainsString('<p class="mat-count" role="status" data-material-count>3 materiały.</p>', $html);
        $this->assertStringContainsString('<h2 class="sr-only">Lista materiałów</h2>', $html);
        $this->assertMatchesRegularExpression('~data-material-reset\s+hidden\s*>Wyczyść filtry</a>~u', $html);
    }

    public function test_filtry_z_adresu_ukrywaja_niepasujace_i_licza_wyniki(): void
    {
        $this->seedMaterials();

        $html = $this->get('/materialy?typ=video&q=webinar')->assertOk()->getContent();

        $this->assertStringContainsString('<p class="mat-count" role="status" data-material-count>Pasujące: 1 materiał z 3.</p>', $html);
        $this->assertMatchesRegularExpression('~<li >\s*<article class="mat-card mat-card--video group"[^>]*data-search="Webinar Office365~u', $html);
        $this->assertMatchesRegularExpression('~<li  hidden >\s*<article class="mat-card mat-card--pdf group"~u', $html);
        $this->assertMatchesRegularExpression('~<input type="radio" name="typ" value="video" checked~u', $html);
        $this->assertStringContainsString('value="webinar"', $html);
        $this->assertMatchesRegularExpression('~class="mat-reset" data-material-reset\s*>Wyczyść filtry</a>~u', $html);

        // Wyszukiwanie bez polskich znaków i wielkości liter.
        $html = $this->get('/materialy?q=DZIEN+DOBRY')->assertOk()->getContent();
        $this->assertStringContainsString('Pasujące: 1 materiał z 3.', $html);

        // Brak wyników: widoczny komunikat z linkiem do wszystkich materiałów.
        $html = $this->get('/materialy?q=nieistniejacy')->assertOk()->getContent();
        $this->assertStringContainsString('Pasujące: 0 materiałów z 3.', $html);
        $this->assertMatchesRegularExpression('~<div class="mat-empty" data-material-empty\s*>~u', $html);

        // Nieznane wartości filtrów są pomijane.
        $html = $this->get('/materialy?typ=zly&dla=xyz&sort=hack')->assertOk()->getContent();
        $this->assertStringContainsString('<p class="mat-count" role="status" data-material-count>3 materiały.</p>', $html);
    }

    public function test_grupy_tematy_i_sortowanie(): void
    {
        EducationalMaterial::create(['title' => 'Bezpieczny PDF', 'description' => 'Poradnik', 'type' => 'pdf', 'is_published' => true, 'target_group' => 'nauczyciele', 'category' => 'dokumenty', 'order' => 1]);
        EducationalMaterial::create(['title' => 'Alt w grafikach', 'description' => 'Lekcja', 'type' => 'scenariusz', 'is_published' => true, 'target_group' => 'graficy', 'category' => 'wcag', 'order' => 2]);
        EducationalMaterial::create(['title' => 'Kurs WCAG', 'description' => 'Nagranie', 'type' => 'video', 'video_url' => 'https://youtu.be/abcdefghijk', 'is_published' => true, 'target_group' => 'graficy', 'category' => 'wcag', 'order' => 3]);

        $html = $this->get('/materialy?dla=graficy')->assertOk()->getContent();

        $this->assertStringContainsString('<option value="graficy" selected>Graficy i projektanci UI (2)</option>', $html);
        $this->assertStringContainsString('<label for="material-topic" class="mat-label">Temat</label>', $html);
        $this->assertStringContainsString('<option value="wcag" >WCAG i standardy dostępności (2)</option>', $html);
        $this->assertStringContainsString('Pasujące: 2 materiały z 3.', $html);
        // Grupa bez pasujących ukryta, grupy mają nagłówki H2 z liczbą widocznych.
        $this->assertMatchesRegularExpression('~<section id="grupa-0" class="scroll-mt-24"\s+aria-labelledby="grupa-h-0"\s+data-material-group\s+hidden\s*>~u', $html);
        $this->assertMatchesRegularExpression('~<h2 id="grupa-h-1" class="mat-group-h">\s*Graficy i projektanci UI\s*<span class="mat-group-count" data-material-group-count>2 materiały</span>~u', $html);
        // Scenariusz: kafelek w kolorze akcentu, temat jako zwykły tekst pod tytułem.
        $this->assertStringContainsString('mat-card mat-card--scenariusz', $html);
        $this->assertStringContainsString('<span class="mat-meta-item">WCAG i standardy dostępności</span>', $html);

        // Sortowanie A–Z liczy serwer.
        $html = $this->get('/materialy?sort=az')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'data-search="Bezpieczny PDF'), strpos($html, 'data-search="Alt w grafikach'));
        $this->assertStringContainsString('<option value="az" selected>Tytuł A–Z</option>', $html);
    }

    public function test_komunikat_z_panelu_jako_jedna_linia_bez_bloku(): void
    {
        $this->seedMaterials();
        $this->useTemplate('feer', ['materials_notice' => '<p>Sukcesywnie przenosimy karty pracy.</p>']);

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<div class="mat-notice">\s*<i class="fa-solid fa-circle-info" aria-hidden="true"></i>\s*<div><p>Sukcesywnie przenosimy karty pracy\.</p></div>~u', $html);
        $this->assertStringNotContainsString('mb-10 max-w-3xl rounded-md bg-gray-50 p-5', $html);
    }

    private function newsletterModule(bool $active): void
    {
        \Closure::bind(function () use ($active) {
            $this->statuses->put('newsletter', $active ? 'active' : 'inactive');
        }, app(\App\Modules\ModuleManager::class), \App\Modules\ModuleManager::class)();
        $this->useTemplate('feer');
    }

    public function test_zapis_przez_feerletter_gdy_modul_newslettera_wlaczony(): void
    {
        $this->seedMaterials();
        $this->newsletterModule(true);

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<section id="zapis" class="relative isolate[^"]*bg-brand-light[^"]*" aria-labelledby="zapis-h">~u', $html);
        $this->assertStringContainsString('<h2 id="zapis-h" class="mt-1 text-xl font-bold leading-snug">Nowe materiały prosto do Twojej skrzynki</h2>', $html);
        $this->assertStringContainsString('href="' . route('newsletter.show') . '"', $html);
        $this->assertStringContainsString('Zapisuję się na newsletter <span aria-hidden="true">→</span>', $html);
        $this->assertStringNotContainsString(route('materials.subscribe'), $html);
        $this->assertStringNotContainsString('Powiadomienia o nowych materiałach', $html);
    }

    public function test_bez_modulu_newslettera_zostaje_formularz_powiadomien(): void
    {
        $this->seedMaterials();
        $this->newsletterModule(false);

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertStringContainsString('Powiadomienia o nowych materiałach', $html);
        $this->assertStringContainsString('action="' . route('materials.subscribe') . '"', $html);
        $this->assertStringNotContainsString('Nowe materiały prosto do Twojej skrzynki', $html);
    }

    public function test_inne_szablony_bez_zmian(): void
    {
        $this->seedMaterials();
        $this->useTemplate('ngo_mix');

        $this->get('/materialy')->assertOk()->assertSee('Webinar Office365')->assertDontSee('mat-card', false);
    }
}
