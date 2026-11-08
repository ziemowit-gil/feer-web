<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Długie sekcje Ustawień podzielone na karty (admin/partials/section-cards): znaczniki, skrypt i powrót do karty po zapisie. */
class SettingsSectionCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Karty (slug => etykieta) w grupie data-cards="$group". */
    private function cards(string $html, string $group): array
    {
        $start = strpos($html, 'data-cards="'.$group.'"');
        $this->assertNotFalse($start, "brak grupy kart {$group}");
        $next = strpos($html, 'data-cards="', $start + 12);
        $chunk = substr($html, $start, $next === false ? null : $next - $start);
        preg_match_all('/data-card="([a-z0-9-]+)" data-card-label="([^"]+)"/', $chunk, $m);

        return array_combine($m[1], $m[2]);
    }

    public function test_serwis_ma_karty_a_stan_witryny_zostaje_na_gorze(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit', ['tab' => 'maintenance']))->assertOk()->getContent();

        $this->assertSame([
            'przerwa' => 'Przerwa techniczna',
            'w-budowie' => 'Dopiero tworzymy',
            'komunikat' => 'Komunikat',
            'zaloba' => 'Żałoba narodowa',
            'adres' => 'Adres techniczny',
        ], $this->cards($html, 'maintenance'));

        // Podsumowanie stanu nie jest kartą — widać je nad paskiem kart.
        $group = strpos($html, 'data-cards="maintenance"');
        $status = strpos($html, 'Stan witryny teraz', $group);
        $this->assertLessThan(strpos($html, 'data-card="przerwa"', $group), $status);

        // Pola bez zmian, skrypt kart i pole powrotu do karty w formularzu.
        foreach (['name="maintenance_mode"', 'name="under_construction"', 'name="notice_text"', 'name="mourning_from"', 'name="site_url"'] as $field) {
            $this->assertStringContainsString($field, $html, $field);
        }
        $this->assertStringContainsString('window.adminSectionCards', $html);
        $this->assertStringContainsString('name="_redirect_card"', $html);
        $this->assertStringContainsString('Zapisujesz całą tę sekcję (wszystkie jej karty)', $html);
    }

    public function test_dlugie_sekcje_maja_karty_z_unikalnymi_slugami(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit', ['tab' => 'general']))->assertOk()->getContent();

        $expected = [
            'general' => ['tozsamosc', 'logo', 'edycja', 'cookies', 'stopka'],
            'colors' => ['marka', 'submarki'],
            'social' => ['media', 'bip'],
            'content' => ['dzialania', 'materialy', 'aktualnosci', 'wolontariat'],
            'modules' => ['moduly', 'typy-tresci'],
            'homepage' => ['uklad', 'slider', 'ankieta', 'wydarzenia', 'pasek'],
            'template' => ['szablon', 'podcasty', 'opcje-szablonu'],
            'login' => ['microsoft', 'google', 'klucze', 'sso', 'strefa', 'cleantalk', '2fa', 'przelewy24', 'adres-panelu'],
            'mail' => ['autokonfigurator', 'wysylka', 'graph'],
        ];
        foreach ($expected as $group => $slugs) {
            $cards = $this->cards($html, $group);
            foreach ($slugs as $slug) {
                $this->assertArrayHasKey($slug, $cards, "{$group}: {$slug}");
            }
        }

        preg_match_all('/data-card="([a-z0-9-]+)"/', $html, $m);
        $this->assertSame($m[1], array_values(array_unique($m[1])), 'slug karty powtarza się na stronie');

        // Pola z kart dalej są w formularzu.
        foreach (['name="microsoft_login_enabled"', 'name="materials_notice"', 'name="news_layout"', 'name="homepage_banner_text"', 'name="bip_mode"', 'name="facebook_url"'] as $field) {
            $this->assertStringContainsString($field, $html, $field);
        }
    }

    public function test_po_zapisie_wraca_do_otwartej_karty(): void
    {
        $this->actingAs($this->admin())->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['mourning_mode', 'mourning_from', 'mourning_until'],
            '_redirect_tab' => 'maintenance',
            '_redirect_card' => 'zaloba',
            'mourning_mode' => '1',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'maintenance', 'karta' => 'zaloba']));

        $this->assertTrue((bool) SiteSetting::query()->first()->mourning_mode);

        // Dziwna wartość karty jest pomijana.
        $this->actingAs($this->admin())->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['mourning_mode'],
            '_redirect_tab' => 'maintenance',
            '_redirect_card' => '"><script>',
        ])->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'maintenance']));
    }
}
