<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stopka szablonu „kreatywni”: na stronie głównej nie powtarza telefonu i e-maila z sekcji „Kontakt” tuż nad nią,
 * a dolny pas bez linków nie ma pustej listy.
 */
class KreatywniFooterTest extends TestCase
{
    use RefreshDatabase;

    private function resetCache(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    protected function tearDown(): void
    {
        $this->resetCache();
        parent::tearDown();
    }

    private function useTemplate(array $extra = []): void
    {
        $this->resetCache();
        SiteSetting::current()->forceFill(['site_template' => 'kreatywni', 'contact_phone' => '+48 663 182 816', 'contact_email' => 'warsztaty@example.org'] + $extra)->save();
        $this->resetCache();
    }

    private function footer(string $html): string
    {
        return substr($html, strpos($html, '<footer class="kc kc-dark kc-footer">'));
    }

    public function test_strona_glowna_nie_dubluje_kontaktu_w_stopce_a_inne_strony_tak(): void
    {
        $this->useTemplate(['contact_city' => 'Kraków i okolice']);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="kontakt"', $home);
        $foot = $this->footer($home);
        $this->assertStringNotContainsString('mailto:warsztaty@example.org', $foot);
        $this->assertStringNotContainsString('tel:+48663182816', $foot);
        $this->assertStringContainsString('Kraków i okolice', $foot, 'adres zostaje');

        $other = $this->footer($this->get('/aktualnosci')->getContent());
        $this->assertStringContainsString('mailto:warsztaty@example.org', $other);
    }

    public function test_bez_sekcji_kontakt_i_bez_linkow_w_dolnym_pasie(): void
    {
        $this->useTemplate(['kreatywni_texts' => ['_hidden' => ['team', 'contact']], 'footer_options' => [
            'show_accessibility' => false, 'show_sitemap' => false, 'show_cookies' => false, 'staff_link' => false, 'show_admin_link' => false,
        ]]);

        $foot = $this->footer($this->get('/')->assertOk()->getContent());
        $this->assertStringContainsString('mailto:warsztaty@example.org', $foot, 'bez sekcji Kontakt stopka pokazuje dane');
        $bottom = substr($foot, strpos($foot, 'kc-foot-bottom'));
        $this->assertStringNotContainsString('<ul>', substr($bottom, 0, strpos($bottom, 'kc-foot-credit') ?: strlen($bottom)));
    }
}
