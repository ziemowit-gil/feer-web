<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Widok modułów: karty z przełącznikami w Ustawienia → Moduły (moduły wbudowane,
 * rozszerzenia, „Typy treści i układy") oraz strona „Moduły systemu".
 */
class ModulesViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettings();
    }

    private function resetSettings(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Minimalny poprawny zapis ustawień (jak w SettingsFormStructureTest). */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name'       => 'Testowa witryna',
            'brand_color'     => '#1e6dff',
            'header_layout'   => 'classic',
            'content_editor'  => 'tinymce',
            'mail_transport'  => 'default',
            'contact_address' => 'ul. Testowa 1',
            'contact_city'    => '00-001 Warszawa',
            'contact_email'   => 'kontakt@example.pl',
        ], $overrides);
    }

    /** Wszystkie wartości każdej grupy jako dostępne — punkt wyjścia do zmian. */
    private function allAllowed(): array
    {
        return array_map(fn (array $options) => array_map('strval', array_keys($options)), SiteSetting::blockableOptionGroups());
    }

    public function test_kazdy_modul_ma_karte_z_przelacznikiem_w_grupie(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.ustawienia.edit', ['tab' => 'modules']))
            ->assertOk()
            ->getContent();

        foreach (SiteSetting::MODULES as $key => $label) {
            $this->assertMatchesRegularExpression(
                '/<input type="checkbox" role="switch" id="module-switch-'.preg_quote($key, '/').'"\s+name="enabled_modules\[\]" value="'.preg_quote($key, '/').'"/',
                $html,
                "Brak przełącznika modułu [{$key}]."
            );
            $this->assertStringContainsString(e($label), $html);
        }

        foreach (['Treści', 'Komunikacja i społeczność', 'Organizacja i dokumenty', 'Wsparcie i sprzedaż', 'Narzędzia'] as $group) {
            $this->assertStringContainsString('>'.$group.'</h2>', $html);
        }

        $this->assertStringContainsString('Włączony', $html);
        $this->assertStringContainsString('aria-pressed', $html);
        $this->assertStringContainsString('role="status" aria-live="polite"', $html);
        $this->assertStringContainsString('błąd 404', $html);
    }

    public function test_kazdy_modul_jest_przypisany_do_grupy(): void
    {
        $grouped = collect(SiteSetting::groupedModules())->flatMap(fn ($g) => array_keys($g['modules']))->all();

        $this->assertEqualsCanonicalizing(array_keys(SiteSetting::MODULES), $grouped);
        $this->assertSame([], array_diff(array_keys(SiteSetting::MODULES), array_keys(SiteSetting::MODULE_META)),
            'Każdy moduł powinien mieć opis w MODULE_META (inaczej trafia do „Inne").');
    }

    public function test_rozszerzenia_sa_na_liscie_ze_stanem_i_formularzami_poza_formularzem_ustawien(): void
    {
        $manager = app(ModuleManager::class);
        $html = $this->actingAs($this->admin())
            ->get(route('admin.ustawienia.edit', ['tab' => 'modules']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Moduły instalowane (rozszerzenia)', $html);

        $settingsFormEnd = strpos($html, '</form>', strpos($html, 'action="'.route('admin.ustawienia.update').'"'));

        foreach ($manager->all() as $id => $manifest) {
            $this->assertStringContainsString(e($manifest->name), $html);
            $this->assertStringContainsString('form="plugin-module-form-'.$id.'"', $html);

            $formPos = strpos($html, 'id="plugin-module-form-'.$id.'"');
            $this->assertNotFalse($formPos, "Brak formularza akcji rozszerzenia [{$id}].");
            $this->assertGreaterThan($settingsFormEnd, $formPos, 'Formularz akcji nie może być zagnieżdżony w formularzu ustawień.');
        }

        $this->assertMatchesRegularExpression('/Aktywny|Nieaktywny|Niezainstalowany/', $html);
    }

    public function test_akcja_rozszerzenia_z_ustawien_wraca_do_zakladki_moduly(): void
    {
        $id = app(ModuleManager::class)->all()->keys()->first();
        $this->assertNotNull($id);

        $this->actingAs($this->admin())
            ->post(route('admin.moduly.deactivate', $id), ['return_to' => 'settings'])
            ->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'modules']));

        $this->actingAs($this->admin())
            ->post(route('admin.moduly.activate', $id))
            ->assertRedirect(route('admin.moduly.index'));
    }

    public function test_strona_modulow_systemu_pokazuje_karty(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.moduly.index'))
            ->assertOk()
            ->assertSee('Moduły systemu')
            ->getContent();

        foreach (app(ModuleManager::class)->all() as $id => $manifest) {
            $this->assertStringContainsString(e($manifest->name), $html);
            $this->assertStringContainsString('id="plugin-module-form-'.$id.'"', $html);
        }
    }

    public function test_zapis_z_odznaczonymi_modulami_wylacza_je_jak_dotad(): void
    {
        $enabled = array_values(array_diff(array_keys(SiteSetting::MODULES), ['gallery', 'polls']));

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->settingsPayload(['enabled_modules' => $enabled]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->resetSettings();
        $this->assertEqualsCanonicalizing(['gallery', 'polls'], SiteSetting::current()->disabled_modules);
    }

    public function test_przelaczniki_typow_tresci_zapisuja_blocked_options(): void
    {
        $allowed = $this->allAllowed();
        $allowed['news_layouts'] = array_values(array_diff($allowed['news_layouts'], ['side']));
        $allowed['page_templates'] = array_values(array_diff($allowed['page_templates'], ['minimal']));

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->settingsPayload([
                'blocked_options_form' => '1',
                'allowed_options' => $allowed,
            ]))
            ->assertSessionHasNoErrors();

        $this->resetSettings();
        $settings = SiteSetting::current();
        $this->assertSame(['side'], $settings->blocked_options['news_layouts']);
        $this->assertSame(['minimal'], $settings->blocked_options['page_templates']);
        $this->assertSame([], $settings->blocked_options['header_layouts']);

        // Widok pokazuje zapisany stan.
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit', ['tab' => 'modules']))->getContent();
        $this->assertStringContainsString('Typy treści i układy', $html);
        $this->assertMatchesRegularExpression('/id="option-switch-news_layouts-side"\s+name="allowed_options\[news_layouts\]\[\]" value="side"\s+aria-label/', $html);
        $this->assertMatchesRegularExpression('/id="option-switch-news_layouts-wide"\s+name="allowed_options\[news_layouts\]\[\]" value="wide" checked/', $html);
    }

    public function test_bez_sekcji_w_zadaniu_blokady_zostaja_bez_zmian(): void
    {
        SiteSetting::current()->update(['blocked_options' => ['news_layouts' => ['side']]]);
        $this->resetSettings();

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->settingsPayload())
            ->assertSessionHasNoErrors();

        $this->resetSettings();
        $this->assertSame(['side'], SiteSetting::current()->blocked_options['news_layouts']);
    }

    public function test_nie_mozna_ukryc_calej_grupy(): void
    {
        $allowed = $this->allAllowed();
        $allowed['header_layouts'] = [];

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->settingsPayload([
                'blocked_options_form' => '1',
                'allowed_options' => $allowed,
            ]))
            ->assertSessionHasErrors('allowed_options.header_layouts');

        $this->resetSettings();
        $this->assertEmpty(SiteSetting::current()->blocked_options['header_layouts'] ?? []);
    }

    public function test_domyslne_opcje_awaryjne_sa_zawsze_dostepne(): void
    {
        $allowed = $this->allAllowed();
        $allowed['page_types'] = array_values(array_diff($allowed['page_types'], ['standard']));
        $allowed['page_templates'] = ['wide'];
        $allowed['news_layouts'] = [];

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->settingsPayload([
                'blocked_options_form' => '1',
                'allowed_options' => $allowed,
            ]))
            ->assertSessionHasNoErrors();

        $this->resetSettings();
        $blocked = SiteSetting::current()->blocked_options;
        $this->assertNotContains('standard', $blocked['page_types']);
        $this->assertNotContains('default', $blocked['page_templates']);
        $this->assertNotContains('default', $blocked['news_layouts']);
        $this->assertContains('portal', $blocked['news_layouts']);

        // Przełączniki chronionych opcji są nieaktywne z objaśnieniem.
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit', ['tab' => 'modules']))->getContent();
        $this->assertMatchesRegularExpression('/id="option-switch-page_types-standard"[^>]*checked disabled/', $html);
        $this->assertStringContainsString('Zawsze dostępny', $html);
    }

    public function test_zablokowany_uklad_artykulu_znika_z_formularza_i_jest_odrzucany(): void
    {
        SiteSetting::current()->update(['blocked_options' => ['news_layouts' => ['side', 'portal']]]);
        $this->resetSettings();
        $admin = $this->admin();

        // Nowy wpis: zablokowanych układów nie ma w wyborze.
        $html = $this->actingAs($admin)->get(route('admin.newsy.create'))->assertOk()->getContent();
        $this->assertStringContainsString('name="article_layout" value="wide"', $html);
        $this->assertStringNotContainsString('name="article_layout" value="side"', $html);
        $this->assertStringNotContainsString('name="article_layout" value="portal"', $html);

        $this->actingAs($admin)->post(route('admin.newsy.store'), [
            'title' => 'Wpis', 'published_at' => now()->toDateTimeString(), 'article_layout' => 'side',
        ])->assertSessionHasErrors('article_layout');

        // Istniejący wpis z zablokowanym układem: bieżący układ zostaje widoczny i dozwolony.
        $news = News::create(['title' => 'Stary', 'slug' => 'stary', 'content' => '<p>x</p>', 'published_at' => now(), 'is_published' => true, 'article_layout' => 'portal']);

        $this->flushSession(); // bez old() z odrzuconego zapisu powyżej
        $html = $this->actingAs($admin)->get(route('admin.newsy.edit', $news))->assertOk()->getContent();
        $this->assertStringContainsString('name="article_layout" value="portal"', $html);
        $this->assertStringNotContainsString('name="article_layout" value="side"', $html);

        $this->actingAs($admin)->put(route('admin.newsy.update', $news), [
            'title' => 'Stary', 'published_at' => now()->toDateTimeString(), 'article_layout' => 'portal',
        ])->assertSessionDoesntHaveErrors('article_layout');

        $this->actingAs($admin)->put(route('admin.newsy.update', $news), [
            'title' => 'Stary', 'published_at' => now()->toDateTimeString(), 'article_layout' => 'side',
        ])->assertSessionHasErrors('article_layout');
    }
}
