<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Prawa strona nagłówka FEER (ikony social i przycisk) edytowana w Ustawienia → Nagłówek (pole JSON header_options). */
class HeaderOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
        SiteSetting::current()->update([
            'site_template' => 'feer',
            'facebook_url' => 'https://facebook.com/feer',
            'facebook_group_url' => 'https://facebook.com/groups/feer',
            'instagram_url' => 'https://instagram.com/feer',
        ]);
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    /** Prawa strona nagłówka FEER na szerokim ekranie: blok z ikonami i przyciskiem obok logo (bez paska górnego i menu mobilnego). */
    private function headerRight(string $html): string
    {
        $start = strpos($html, '<div class="hidden items-center gap-3 lg:flex">');
        if ($start === false) {
            return '';
        }
        $end = strpos($html, '<nav aria-label="Menu główne"', $start);

        return substr($html, $start, ($end ?: $start + 6000) - $start);
    }

    public function test_domyslnie_ikony_i_przycisk_materialow_jak_dotad(): void
    {
        $header = $this->headerRight($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('class="feer-social', $header);
        $this->assertStringContainsString('aria-label="Facebook — otwiera się w nowej karcie"', $header);
        $this->assertStringContainsString('aria-label="Instagram — otwiera się w nowej karcie"', $header);
        $this->assertStringContainsString('Materiały edukacyjne', $header);
        $this->assertStringContainsString('fa-book-open', $header);
    }

    public function test_administrator_zmienia_ikony_i_przycisk(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['header_options[show_social]', 'header_options[social][]', 'header_options[show_cta]', 'header_options[cta_label]', 'header_options[cta_url]', 'header_options[cta_icon]'],
            'header_options' => [
                'show_social' => '1', 'social' => ['instagram'],
                'show_cta' => '1', 'cta_label' => 'Wesprzyj nas', 'cta_url' => '/wsparcie', 'cta_icon' => 'fa-hand-holding-heart',
            ],
        ])->assertSessionHasNoErrors();
        $this->reset();

        auth()->logout();
        $header = $this->headerRight($this->get('/')->assertOk()->getContent());
        $this->assertStringContainsString('aria-label="Instagram — otwiera się w nowej karcie"', $header);
        $this->assertStringNotContainsString('aria-label="Facebook — otwiera się w nowej karcie"', $header);
        $this->assertStringContainsString('<a href="/wsparcie"', $header);
        $this->assertStringContainsString('fa-hand-holding-heart', $header);
        $this->assertStringContainsString('Wesprzyj nas', $header);
        $this->assertStringNotContainsString('Materiały edukacyjne', $header);
    }

    public function test_mozna_wylaczyc_ikony_i_przycisk(): void
    {
        SiteSetting::current()->update(['header_options' => SiteSetting::normalizeHeaderOptions(['show_social' => false, 'show_cta' => false])]);
        $this->reset();

        $header = $this->headerRight($this->get('/')->assertOk()->getContent());
        $this->assertStringNotContainsString('class="feer-social', $header);
        $this->assertStringNotContainsString('fa-book-open', $header);
    }

    public function test_bledny_adres_i_ikona_sa_odrzucane(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['header_options[cta_url]', 'header_options[cta_icon]'],
            'header_options' => ['cta_url' => 'javascript:alert(1)', 'cta_icon' => 'fa-skull'],
        ])->assertSessionHasErrors(['header_options.cta_url', 'header_options.cta_icon']);

        // Normalizacja też nie przepuści złych wartości (np. zapis z innego miejsca).
        $o = SiteSetting::normalizeHeaderOptions(['cta_url' => '//obca.pl', 'cta_icon' => 'fa-skull', 'social' => ['instagram', 'nieznany', 'facebook']]);
        $this->assertSame('', $o['cta_url']);
        $this->assertSame('fa-book-open', $o['cta_icon']);
        $this->assertSame(['facebook', 'instagram'], $o['social']);
    }

    public function test_zapis_innej_sekcji_nie_zeruje_naglowka(): void
    {
        SiteSetting::current()->update(['header_options' => SiteSetting::normalizeHeaderOptions(['cta_label' => 'Mój przycisk'])]);
        $this->reset();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['tagline'], 'tagline' => 'Nowy podtytuł',
        ])->assertSessionHasNoErrors();
        $this->reset();

        $this->assertSame('Mój przycisk', SiteSetting::current()->headerOptions()['cta_label']);
    }

    public function test_ustawienia_naglowka_maja_sekcje_a_pasek_panelu_odnosnik(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'header']))->assertOk()
            ->assertSee('id="header-right"', false)
            ->assertSee('Ikony mediów społecznościowych i przycisk')
            ->assertSee('name="header_options[icons_set]"', false)
            ->assertSee('instagram.com', false)
                        ->assertSee('name="header_options[cta_label]"', false)
            ->assertSee('Dodaj ikonę');

        $this->actingAs($admin)->get('/')->assertOk()
            ->assertSee('<span>Edytuj nagłówek</span>', false)
            ->assertSee('tab=header#header-right', false);
    }

    /** Stopka FEER: blok ikon social w ciemnym pasie. */
    private function footerSocial(string $html): string
    {
        $start = strpos($html, 'aria-label="Media społecznościowe i skróty"');

        return $start === false ? '' : substr($html, $start, 6000);
    }

    public function test_lista_ikon_z_ustawien_naglowka_zastepuje_profile(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['header_options[show_social]', 'header_options[icons_set]', 'header_options[icons][0][url]'],
            'header_options' => [
                'show_social' => '1',
                'icons_set' => '1',
                'icons' => [
                    ['icon' => 'bi bi-messenger', 'label' => 'Fundacja na Facebooku', 'url' => 'https://facebook.com/feer', 'header' => '1', 'footer' => '0'],
                    ['icon' => 'bi bi-instagram', 'label' => '', 'url' => 'https://instagram.com/feer', 'header' => '0', 'footer' => '1'],
                ],
            ],
        ])->assertSessionHasNoErrors();
        $this->reset();
        auth()->logout();

        $html = $this->get('/')->assertOk()->getContent();
        $header = $this->headerRight($html);
        $this->assertStringContainsString('bi bi-messenger', $header);
        $this->assertStringContainsString('aria-label="Fundacja na Facebooku — otwiera się w nowej karcie"', $header);
        $this->assertStringNotContainsString('Instagram — otwiera', $header);
        // Grupa na Facebooku ma adres w Media i BIP, ale nie ma jej na liście — szablon FEER jej nie pokazuje.
        $this->assertStringNotContainsString('groups/feer', $header);

        $footer = $this->footerSocial($html);
        $this->assertStringContainsString('aria-label="Instagram — otwiera się w nowej karcie"', $footer);
        $this->assertStringNotContainsString('facebook.com', $footer);
    }

    public function test_pusta_lista_oznacza_brak_ikon(): void
    {
        SiteSetting::current()->update(['header_options' => SiteSetting::normalizeHeaderOptions(['icons_set' => '1'])]);
        $this->reset();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('class="feer-social', $this->headerRight($html));
        $this->assertStringNotContainsString('facebook.com', $this->footerSocial($html));
    }

    public function test_bez_wyboru_stopka_pokazuje_wszystkie_profile_jak_dotad(): void
    {
        $footer = $this->footerSocial($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('aria-label="Facebook — otwiera się w nowej karcie"', $footer);
        $this->assertStringContainsString('aria-label="Grupa na Facebooku — otwiera się w nowej karcie"', $footer);
        $this->assertStringContainsString('aria-label="Instagram — otwiera się w nowej karcie"', $footer);
    }

    public function test_inne_ikony_z_wlasnym_adresem_w_wybranych_miejscach(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['header_options[icons][0][url]'],
            'header_options' => [
                'show_social' => '1',
                'icons' => [
                    ['icon' => 'bi bi-instagram', 'label' => '', 'url' => 'https://instagram.com/feer', 'header' => '1', 'footer' => '0'],
                    ['icon' => 'bi bi-tiktok', 'label' => '', 'url' => 'https://tiktok.com/@feer', 'header' => '1', 'footer' => '0'],
                    ['icon' => 'bi bi-envelope', 'label' => 'Napisz do nas', 'url' => 'mailto:biuro@example.org', 'header' => '0', 'footer' => '1'],
                ],
            ],
        ])->assertSessionHasNoErrors();
        $this->reset();
        auth()->logout();

        $html = $this->get('/')->assertOk()->getContent();
        $header = $this->headerRight($html);
        $this->assertStringContainsString('aria-label="TikTok — otwiera się w nowej karcie"', $header);
        $this->assertStringNotContainsString('Napisz do nas', $header);
        $this->assertLessThan(strpos($header, 'bi bi-tiktok'), strpos($header, 'bi bi-instagram'));

        $footer = $this->footerSocial($html);
        $this->assertStringContainsString('href="mailto:biuro@example.org"', $footer);
        $this->assertStringContainsString('aria-label="Napisz do nas"', $footer);
        $this->assertStringNotContainsString('TikTok', $footer);
    }

    public function test_inna_ikona_z_blednym_adresem_jest_odrzucana(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['header_options[icons][0][url]'],
            'header_options' => ['icons' => [['icon' => 'bi bi-skull', 'url' => 'javascript:alert(1)']]],
        ])->assertSessionHasErrors(['header_options.icons.0.url', 'header_options.icons.0.icon']);

        $this->assertSame([], SiteSetting::normalizeFeerIcons([['icon' => 'bi bi-tiktok', 'url' => '//obca.pl'], ['icon' => 'x', 'url' => 'https://a.pl']]));
    }

    /** Produkcja: „classic” zablokowany w opcjach instalacji, zapisany office_bar — zapis sekcji nagłówka FEER nie może padać. */
    public function test_zablokowany_uklad_nie_blokuje_zapisu_naglowka_feer(): void
    {
        SiteSetting::current()->update([
            'header_layout' => 'office_bar',
            'blocked_options' => ['header_layouts' => ['classic', 'brand_bar', 'brand_bar_inline', 'wide_mission']],
        ]);
        $this->reset();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['header_layout', 'header_options[show_social]', 'header_options[icons_set]'],
            'header_layout' => 'classic',
            'header_options' => ['show_social' => '1', 'icons_set' => '1'],
        ])->assertSessionHasNoErrors();
        $this->reset();

        $this->assertSame('office_bar', SiteSetting::current()->header_layout);
    }
}
