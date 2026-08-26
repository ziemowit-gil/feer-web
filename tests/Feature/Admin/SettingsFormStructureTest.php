<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Strażnik struktury formularza ustawień. Zagnieżdżony <form> jest w HTML
 * niedozwolony: przeglądarka zamyka nim formularz nadrzędny, więc pola z dalszych
 * zakładek i przycisk „Zapisz" przestają do niego należeć i nic się nie zapisuje.
 */
class SettingsFormStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    /** Pozycja znacznika <form> zapisującego ustawienia (nie linku w menu). */
    private function settingsFormStart(string $html): int
    {
        $pattern = '/<form[^>]+action="'.preg_quote(route('admin.ustawienia.update'), '/').'"/';

        $this->assertSame(1, preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE),
            'Nie znaleziono formularza zapisu ustawień.');

        // Za znacznikiem otwierającym — sam <form> nie może liczyć się jako zagnieżdżony.
        return $m[0][1] + strlen($m[0][0]);
    }

    public function test_glowny_formularz_ustawien_nie_jest_przedwczesnie_zamykany(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.ustawienia.edit'))
            ->assertOk()
            ->getContent();

        $start = $this->settingsFormStart($html);

        $save = strpos($html, '>Zapisz</button>', $start);
        $this->assertNotFalse($save, 'Nie znaleziono przycisku „Zapisz".');

        $body = substr($html, $start, $save - $start);

        $this->assertStringNotContainsString('</form>', $body,
            'Formularz ustawień zamyka się przed przyciskiem „Zapisz" — najpewniej przez zagnieżdżony <form>.');
        $this->assertStringNotContainsString('<form', $body,
            'W formularzu ustawień jest zagnieżdżony <form>. Zamiast tego użyj atrybutu form="…" na kontrolce.');
    }

    public function test_nieobslugiwany_uklad_naglowka_z_bazy_nie_blokuje_zapisu(): void
    {
        // Starsze bazy mają header_layout = 'default' / content_editor = 'quill'.
        // Bez normalizacji żaden radio nie byłby zaznaczony, przeglądarka nie
        // wysłałaby wymaganego pola i zapis ustawień cicho by padał.
        $settings = SiteSetting::current();
        $settings->forceFill(['header_layout' => 'default', 'content_editor' => 'quill'])->save();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->assertSame('classic', $settings->fresh()->headerLayoutValue());
        $this->assertSame('tinymce', $settings->fresh()->contentEditorValue());

        $html = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.ustawienia.edit'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<input type="radio" name="header_layout" value="classic"[^>]*checked/',
            preg_replace('/\s+/', ' ', $html),
            'Żaden układ nagłówka nie jest zaznaczony — przeglądarka nie wyśle wymaganego pola.',
        );
    }

    public function test_formularz_ma_novalidate_zeby_ukryte_pola_nie_blokowaly_zapisu(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.ustawienia.edit'))
            ->assertOk()
            ->getContent();

        $open = strrpos(substr($html, 0, $this->settingsFormStart($html)), '<form');
        $tag  = substr($html, $open, strpos($html, '>', $open) - $open);

        $this->assertStringContainsString('novalidate', $tag,
            'Bez novalidate puste pole wymagane w ukrytej zakładce blokuje wysyłkę bez komunikatu.');
    }

    public function test_blad_walidacji_pokazuje_sie_w_podsumowaniu_na_gorze(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            'site_name'       => '',
            'brand_color'     => '#c31432',
            'header_layout'   => 'classic',
            'content_editor'  => 'tinymce',
            'mail_transport'  => 'default',
            'contact_address' => 'ul. Testowa 1',
            'contact_city'    => '00-001 Warszawa',
            'contact_email'   => 'kontakt@example.pl',
        ]);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit'))
            ->assertOk()
            ->assertSee('Ustawienia nie zostały zapisane')
            ->assertSee('data-settings-error-field="site_name"', false);
    }

    public function test_pole_z_zakladki_kontakt_jest_w_formularzu_zapisu(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.ustawienia.edit'))
            ->assertOk()
            ->getContent();

        $start = $this->settingsFormStart($html);

        // Pierwsze </form> po otwarciu zamyka formularz ustawień (brak zagnieżdżeń
        // pilnuje test powyżej), więc tylko to, co przed nim, zostanie wysłane.
        $end = strpos($html, '</form>', $start);
        $this->assertNotFalse($end, 'Formularz ustawień nie jest zamknięty.');

        $body = substr($html, $start, $end - $start);

        $this->assertStringContainsString('>Zapisz</button>', $body,
            'Przycisk „Zapisz" jest poza formularzem ustawień — kliknięcie nic nie wyśle.');

        foreach (['name="contact_email"', 'name="contact_address"', 'name="mail_transport"'] as $field) {
            $this->assertStringContainsString($field, $body,
                "Pole {$field} znalazło się poza formularzem zapisu ustawień.");
        }
    }
}
