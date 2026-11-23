<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Informacja o plikach cookies: strona /informacja-o-cookies z tym, co strona naprawdę zapisuje (statystyki tylko
 * gdy ustawione), wstęp i adres polityki prywatności z panelu, linki w banerze i stopce Kreatywnych, działanie bez migracji.
 */
class CookieInfoTest extends TestCase
{
    use RefreshDatabase;

    private function site(array $attrs = []): SiteSetting
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        if ($attrs !== []) {
            SiteSetting::current()->forceFill($attrs)->save();
            \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        }

        return SiteSetting::current();
    }

    public function test_strona_pokazuje_niezbedne_i_ulatwienia_a_statystyki_tylko_gdy_sa(): void
    {
        $this->site(['ga_measurement_id' => null, 'clarity_project_id' => null]);

        $html = $this->get(route('cookies.info'))->assertOk()
            ->assertSee('Informacja o plikach cookies')
            ->assertSee('Niezbędne')->assertSee('XSRF-TOKEN')->assertSee(config('session.cookie'))
            ->assertSee('Ułatwienia dostępu')
            ->assertSee('Zmień ustawienia cookies')
            ->assertSee('Ta strona nie używa plików cookies, które wymagają zgody.')
            ->getContent();
        $this->assertStringNotContainsString('Google Analytics', $html);

        $this->site(['ga_measurement_id' => 'G-TEST123']);
        $this->get(route('cookies.info'))->assertOk()
            ->assertSee('Statystyki')->assertSee('Google (Google Analytics 4)')->assertSee('_ga, _ga_…')
            ->assertSee('data-cookie-decision-text', false);
    }

    public function test_wstep_i_polityka_prywatnosci_z_panelu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->site();

        // Bez strony /polityka-prywatnosci i bez adresu w panelu — bez linku.
        $this->get(route('cookies.info'))->assertOk()->assertDontSee('polityce prywatności');

        Page::create(['title' => 'Polityka prywatności', 'slug' => 'polityka-prywatnosci', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Tekst</p>']);
        \Illuminate\Support\Once::flush();
        $this->get(route('cookies.info'))->assertOk()->assertSee('/polityka-prywatnosci', false);

        $this->site(['cookie_info_text' => "Pierwszy akapit.\n\nDrugi akapit.", 'privacy_policy_url' => 'https://example.org/rodo']);
        $this->get(route('cookies.info'))->assertOk()
            ->assertSee('<p>Drugi akapit.</p>', false)
            ->assertSee('href="https://example.org/rodo"', false);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()
            ->assertSee('name="cookie_info_text"', false)->assertSee('name="privacy_policy_url"', false);
    }

    public function test_baner_i_stopka_kreatywni_linkuja_do_informacji(): void
    {
        $this->site(['site_template' => 'kreatywni', 'cookie_banner_enabled' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('>Więcej o plikach cookies</a>', $html);
        $this->assertStringContainsString('<li><a href="'.route('cookies.info').'">Informacja o cookies</a></li>', $html);
        $this->assertStringNotContainsString('href="'.url('/polityka-prywatnosci').'"', $html, 'Bez strony polityki nie ma martwego linku.');

        $this->get(route('cookies.info'))->assertOk()->assertSee('class="kc-page-head"', false);
    }

    public function test_dziala_bez_migracji(): void
    {
        Schema::table('site_settings', fn ($t) => $t->dropColumn(['cookie_info_text', 'privacy_policy_url']));
        $this->site();

        $this->get(route('cookies.info'))->assertOk()->assertSee(mb_substr(SiteSetting::DEFAULT_COOKIE_INFO_TEXT, 0, 40));
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()->assertDontSee('name="cookie_info_text"', false);
    }
}
