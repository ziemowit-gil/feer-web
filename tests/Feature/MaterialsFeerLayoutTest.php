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
        $this->assertMatchesRegularExpression('/<p class="mat-meta">\s*<span class="mat-meta-item"><i class="fa-solid fa-video" aria-hidden="true"><\/i> Nagranie wideo<\/span>/u', $html);
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
        $this->assertStringContainsString('class="mat-thumb mat-thumb--video" aria-hidden="true"', $html);
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

    public function test_naglowek_sekcji_z_liczba_wyszukiwarka_i_filtr(): void
    {
        $this->seedMaterials();

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<h2 id="grupa-h-0" class="mat-group-h">\s*Ogólny \(dla wszystkich\)\s*<span class="mat-group-count">3 materiały</span>~u', $html);
        $this->assertStringContainsString('<form role="search"', $html);
        $this->assertStringContainsString('<label for="material-search" class="mat-label">Szukaj materiału</label>', $html);
        $this->assertStringContainsString('role="group" aria-label="Typ materiału"', $html);
        $this->assertStringContainsString('data-material-filter="video" aria-pressed="false">Nagrania</button>', $html);
        $this->assertStringContainsString('data-material-filter="pdf" aria-pressed="false">PDF</button>', $html);
        $this->assertStringNotContainsString('data-material-filter="scenariusz"', $html); // tylko obecne typy
        $this->assertStringContainsString('role="status" data-material-count', $html);
        $this->assertStringContainsString('data-material-tools hidden', $html); // bez JS narzędzia ukryte, lista pełna
    }

    public function test_komunikat_z_panelu_jako_jedna_linia_bez_bloku(): void
    {
        $this->seedMaterials();
        $this->useTemplate('feer', ['materials_notice' => '<p>Sukcesywnie przenosimy karty pracy.</p>']);

        $html = $this->get('/materialy')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<div class="mat-notice">\s*<i class="fa-solid fa-circle-info" aria-hidden="true"></i>\s*<div><p>Sukcesywnie przenosimy karty pracy\.</p></div>~u', $html);
        $this->assertStringNotContainsString('mb-10 max-w-3xl rounded-md bg-gray-50 p-5', $html);
    }

    public function test_inne_szablony_bez_zmian(): void
    {
        $this->seedMaterials();
        $this->useTemplate('ngo_mix');

        $this->get('/materialy')->assertOk()->assertSee('Webinar Office365')->assertDontSee('mat-card', false);
    }
}
