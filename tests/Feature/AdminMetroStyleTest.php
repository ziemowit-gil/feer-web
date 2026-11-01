<?php

namespace Tests\Feature;

use App\Models\AccessibilityReport;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMetro;
use App\Support\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Alternatywny styl panelu „Metro” (Mój profil → Wygląd panelu): wybór, motyw, pulpit z kaflami i kontrast. */
class AdminMetroStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function metroAdmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => 'metro']);
    }

    public function test_metro_jest_na_liscie_stylow_i_mozna_go_wybrac_w_profilu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()
            ->assertSee('name="admin_scheme" value="metro"', false)
            ->assertSee('Alternatywny styl wzorowany na Metro UI');

        $this->actingAs($admin)->patch(route('profile.scheme'), ['admin_scheme' => 'metro'])
            ->assertRedirect(route('profile.edit').'#wyglad-panelu')
            ->assertSessionHas('status', 'Schemat kolorów panelu: Metro.');
        $this->assertSame('metro', $admin->fresh()->adminScheme());
    }

    public function test_admin_ustawia_metro_innej_osobie_w_edycji_uzytkownika(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($admin)->get(route('admin.uzytkownicy.edit', $editor))->assertOk()
            ->assertSee('Metro — Alternatywny styl', false);

        $this->actingAs($admin)->put(route('admin.uzytkownicy.update', $editor), [
            'name' => $editor->name, 'email' => $editor->email, 'role' => $editor->role, 'admin_scheme' => 'metro',
        ])->assertSessionHasNoErrors();
        $this->assertSame('metro', $editor->fresh()->adminScheme());
    }

    public function test_panel_w_stylu_metro_ma_klase_motyw_i_kroj_naglowkow(): void
    {
        $html = $this->actingAs($this->metroAdmin())->get(route('admin.podstrony.index'))->assertOk()->getContent();

        // Wspólny markup z motywem Joomla (klasa admin-joomla zostaje), plus klasa stylu Metro i jego arkusz.
        $this->assertStringContainsString('<body data-scheme="metro" class="admin-joomla admin-metro flex', $html);
        $this->assertStringContainsString('body.admin-metro {', $html);
        $this->assertStringContainsString('--m-radius: 0;', $html);
        $this->assertStringContainsString('family=Montserrat:wght@300;400;600;700', $html);
        // Płasko: bez zaokrągleń i cieni, pola z obrysem 2 px, fokus w kolorze akcentu, cel dotyku 44 px.
        $this->assertStringContainsString('border-radius: var(--m-radius)', $html);
        $this->assertStringContainsString('border: 2px solid var(--m-line-strong)', $html);
        $this->assertStringContainsString('min-height: 2.75rem', $html);
        $this->assertStringContainsString('.tm-head:focus-visible { outline: 3px solid var(--m-focus)', $html);
        // Ruch wyłączony przy prefers-reduced-motion i czytelne tryby kontrastu.
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $html);
        $this->assertStringContainsString('html.contrast-bw .admin-metro', $html);
        // Funkcje menu i selektora witryny bez zmian (ten sam markup).
        $this->assertStringContainsString('admin-sidebar nav-style-flyout', $html);
        $this->assertStringContainsString('x-show="fly && $store.adminNav.flyout()"', $html);
    }

    public function test_pulpit_metro_to_kafle_na_dzis_i_utworz_zamiast_klasycznego_ukladu(): void
    {
        $html = $this->actingAs($this->metroAdmin())->get(route('admin.dashboard'))->assertOk()->getContent();

        foreach (['mt-today' => 'Na dziś', 'mt-create' => 'Utwórz'] as $id => $title) {
            $this->assertMatchesRegularExpression('~<h2 id="'.$id.'" class="metro-group-title">'.preg_quote($title, '~').'</h2>~u', $html, "Brak grupy kafli: $title");
        }
        $this->assertStringContainsString('href="'.route('admin.newsy.create').'" class="metro-tile" data-tone="brand"', $html);
        $this->assertStringContainsString('<span class="sr-only">Utwórz: </span>', $html);
        // Pulpit jest prosty: bez kafli modułów, liczników i klasycznych kafli Joomla (menu boczne zastępuje skróty do modułów).
        foreach (['mt-group-', 'mt-stats', 'class="j-tile', 'id="dash-stats"', 'id="dash-tiles"', 'Panel sterowania', 'Zawartość serwisu'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, $gone);
        }
    }

    public function test_pulpit_metro_bez_spraw_do_decyzji_pokazuje_kafel_komunikat(): void
    {
        // Bez spraw do decyzji: kafel-komunikat „Wszystko załatwione” (role=status), nie link.
        $html = $this->actingAs($this->metroAdmin())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<p class="metro-tile" data-tone="paper" role="status">~', $html);
        $this->assertStringContainsString('Wszystko załatwione', $html);
        $this->assertStringNotContainsString('class="metro-tile metro-tile--count" data-tone="amber"', $html);
    }

    public function test_pulpit_metro_pokazuje_pilna_sprawe_jako_kafel_z_licznikiem(): void
    {
        $admin = $this->metroAdmin();
        AccessibilityReport::create(['email' => 'anna@example.com', 'message' => 'Bariera.']);
        AccessibilityReport::create(['email' => 'jan@example.com', 'message' => 'Druga bariera.']);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<a href="'.preg_quote(route('admin.zgloszenia-barier.index'), '~').'" class="metro-tile metro-tile--count" data-tone="amber">\s*<span class="metro-tile-count">2</span>~u', $html);
        $this->assertStringContainsString('Nowe zgłoszenia barier', $html);
        $this->assertStringNotContainsString('Wszystko załatwione', $html);
    }

    public function test_koordynator_w_metro_ma_kafle_utworz_tylko_ze_swojego_zakresu(): void
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR, 'admin_scheme' => 'metro']);

        $html = $this->actingAs($coordinator)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('admin.wydarzenia.create').'" class="metro-tile" data-tone="brand"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.newsy.create').'" class="metro-tile', $html);
        $this->assertStringNotContainsString('href="'.route('admin.podstrony.create').'" class="metro-tile', $html);
    }

    #[DataProvider('widokiPanelu')]
    public function test_typowe_widoki_panelu_renderuja_sie_w_stylu_metro(string $route): void
    {
        // Lista, formularz i ustawienia: ten sam layout, więc wystarczy, że renderuje się bez błędu i z klasą stylu.
        $html = $this->actingAs($this->metroAdmin())->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('<body data-scheme="metro" class="admin-joomla admin-metro flex', $html, $route);
        $this->assertStringContainsString('id="admin-sidebar"', $html, $route);
        $this->assertStringContainsString('<h1 class="j-title', $html, $route);
    }

    public static function widokiPanelu(): array
    {
        return [
            'lista aktualności' => ['admin.newsy.index'],
            'formularz użytkownika' => ['admin.uzytkownicy.create'],
            'formularz wydarzenia' => ['admin.wydarzenia.create'],
            'lista stron' => ['admin.podstrony.index'],
            'lista działań' => ['admin.projekty.index'],
            'lista wydarzeń' => ['admin.wydarzenia.index'],
            'użytkownicy' => ['admin.uzytkownicy.index'],
            'ustawienia' => ['admin.ustawienia.edit'],
            'menu nawigacji (zakładki)' => ['admin.pozycje-menu.index'],
            'multimedia' => ['admin.multimedia.index'],
        ];
    }

    public function test_pozostale_schematy_zostaja_bez_stylu_metro(): void
    {
        foreach (['krem', 'szary', 'niebieski', 'szalwia', 'pierwotny'] as $scheme) {
            $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => $scheme]);
            $html = $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->getContent();

            $this->assertStringContainsString('<body data-scheme="'.$scheme.'" class="admin-joomla flex', $html, $scheme);
            $this->assertStringNotContainsString('admin-metro', $html, $scheme);
            $this->assertStringNotContainsString('metro-tile', $html, $scheme);
            $this->assertStringNotContainsString('family=Montserrat', $html, $scheme);
            $this->assertStringContainsString('id="dash-attention"', $html, $scheme);
            $this->assertStringContainsString('id="dash-create-menu"', $html, $scheme);
        }
    }

    #[DataProvider('kolorMarki')]
    public function test_kafle_maja_kontrast_co_najmniej_4_5_w_kazdym_stanie(string $brand): void
    {
        $tones = AdminMetro::tones($brand);

        $this->assertSame(['brand', 'amber', 'paper'], array_keys($tones));
        foreach ($tones as $name => $tone) {
            foreach (['bg' => 'normalny', 'hover' => 'najechany', 'active' => 'wciśnięty'] as $state => $label) {
                $ratio = Color::contrast($tone[$state], $tone['fg']);
                $this->assertGreaterThanOrEqual(4.5, $ratio, "Ton {$name} ({$label}, marka {$brand}): kontrast {$ratio}:1");
            }
        }
    }

    public function test_niepoprawny_kolor_marki_daje_domyslne_kafle(): void
    {
        $tones = AdminMetro::tones('nie-kolor');

        // Domyślna marka FEER (#1e6dff) przyciemniona dokładnie tyle, by biały tekst miał ≥ 4,5:1.
        $this->assertSame('#1d68f2', $tones['brand']['bg']);
        $this->assertSame('#ffffff', $tones['brand']['fg']);
        foreach ($tones as $tone) {
            foreach (['bg', 'fg', 'hover', 'active'] as $key) {
                $this->assertTrue(Color::isValid($tone[$key]), $tone[$key]);
            }
        }
    }

    public static function kolorMarki(): array
    {
        return [
            'FEER niebieski' => ['#1e6dff'],
            'pomarańcz' => ['#ea8f00'],
            'żółty' => ['#ffd400'],
            'różowy' => ['#e6006f'],
            'zielony' => ['#00a86b'],
            'prawie czarny' => ['#222222'],
        ];
    }

    public function test_akcent_stylu_i_komunikaty_maja_kontrast(): void
    {
        $brand = AdminMetro::tones(SiteSetting::current()->brandPalette()['color'])['brand'];

        $this->assertGreaterThanOrEqual(4.5, Color::contrast($brand['bg'], $brand['fg']));
        $this->assertGreaterThanOrEqual(4.5, Color::contrast(AdminMetro::DANGER, AdminMetro::WHITE));
        // Obrys pól i paneli (komponent interfejsu) ≥ 3:1 na bieli i na szarym tle panelu.
        $this->assertGreaterThanOrEqual(3.0, Color::contrast('#767676', '#ffffff'));
        $this->assertGreaterThanOrEqual(3.0, Color::contrast('#767676', AdminMetro::SURFACES['page']));
        // Tekst pomocniczy i ink na szarych powierzchniach (tło strony, pasek boczny, najechany wiersz paska i listy, nagłówki tabel).
        foreach (AdminMetro::SURFACES as $name => $surface) {
            $this->assertGreaterThanOrEqual(4.5, Color::contrast(AdminMetro::MUTED, $surface), $name);
            $this->assertGreaterThanOrEqual(4.5, Color::contrast(AdminMetro::INK, $surface), $name);
        }
    }

    public function test_tla_metro_sa_jasne_i_pochodza_z_jednego_miejsca(): void
    {
        // Tło strony, menu i kafle są jasne: żadna powierzchnia (poza żywym akcentem i pomarańczem) nie jest ciemniejsza niż ok. #c6c6c6 nawet po wciśnięciu.
        foreach (AdminMetro::SURFACES as $name => $surface) {
            $this->assertLessThanOrEqual(1.4, Color::contrast($surface, '#ffffff'), "Powierzchnia {$name} jest za ciemna");
        }
        foreach (AdminMetro::tones('#1e6dff') as $name => $tone) {
            if (in_array($name, ['brand', 'amber'], true)) {
                continue; // żywy akcent z białym albo ciemnym tekstem
            }
            $this->assertLessThanOrEqual(1.8, Color::contrast($tone['active'], '#ffffff'), "Kafel {$name} jest za ciemny");
        }

        // Arkusz Metro wypisuje dokładnie te kolory (jedno źródło prawdy: AdminMetro).
        $html = $this->actingAs($this->metroAdmin())->get(route('admin.podstrony.index'))->assertOk()->getContent();
        $this->assertStringContainsString('--m-bg: '.AdminMetro::SURFACES['page'].';', $html);
        $this->assertStringContainsString('--m-side: '.AdminMetro::SURFACES['side'].';', $html);
        foreach (['brand', 'amber', 'paper'] as $tone) {
            $this->assertStringContainsString('.metro-tile[data-tone="'.$tone.'"] {', $html);
        }
        foreach (['ink', 'deep', 'slate', 'sky', 'sand', 'mist'] as $removed) {
            $this->assertStringNotContainsString('.metro-tile[data-tone="'.$removed.'"]', $html, "Ciemny ton {$removed} został usunięty");
        }
    }
}
