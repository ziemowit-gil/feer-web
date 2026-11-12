<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pasek ułatwień dostępu (partials/a11y-panel): podpisane grupy, płaskie przyciski, bieżący rozmiar tekstu, poziom odstępów. */
class A11yPanelTest extends TestCase
{
    use RefreshDatabase;

    private function panel(string $template = 'feer'): string
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update(['site_template' => $template]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $start = strpos($html, '<div id="a11y-panel"');
        $this->assertNotFalse($start, 'pasek dostępności na stronie');

        return substr($html, $start, 9000);
    }

    public function test_pasek_ma_podpisane_grupy_i_plaskie_przyciski(): void
    {
        $panel = $this->panel();

        foreach (['Rozmiar tekstu', 'Czytelność', 'Kontrast'] as $label) {
            $this->assertStringContainsString('<span class="a11yb-lbl" aria-hidden="true">' . $label . '</span>', $panel);
        }
        $this->assertStringContainsString('role="group" aria-label="Rozmiar tekstu"', $panel);
        $this->assertStringContainsString('role="group" aria-label="Czytelność tekstu"', $panel);
        $this->assertStringContainsString('role="group" aria-label="Tryb kontrastu"', $panel);
        // Płasko: bez ramek i separatorów z poprzedniej wersji.
        $this->assertStringNotContainsString('border-gray-300', $panel);
        $this->assertStringNotContainsString('width:1px;height:1.25rem', $panel);
    }

    public function test_rozmiar_tekstu_pokazuje_wartosc_a_odstepy_poziom(): void
    {
        $panel = $this->panel();

        $this->assertStringContainsString('data-a11y-font="reset" class="a11yb-btn a11yb-now" aria-label="Rozmiar tekstu 100% — przywróć domyślny"><span data-a11y-font-value>100%</span>', $panel);
        $this->assertStringContainsString('data-a11y-lh data-a11y-name="Wiersze"', $panel);
        $this->assertStringContainsString('data-a11y-ls data-a11y-name="Litery"', $panel);
        $this->assertSame(2, substr_count($panel, '<span class="a11yb-lvl" aria-hidden="true"><span></span><span></span></span>'));

        $js = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('function syncFontUi(size)', $js);
        $this->assertStringContainsString("btn.dataset.level = String(Math.max(0, idx));", $js);
        $this->assertStringContainsString("btn.dataset.a11yName || 'Rozstrzał liter'", $js);
    }

    public function test_ikony_istnieja_i_nazwy_zawieraja_widoczny_napis(): void
    {
        $panel = $this->panel();

        // fa-vector-square nie ma w Font Awesome 7 Free — przycisk „Fokus” był bez ikony.
        $this->assertStringNotContainsString('fa-vector-square', $panel);
        $this->assertStringContainsString('<i class="fa-solid fa-keyboard" aria-hidden="true"></i>Fokus', $panel);
        $this->assertStringContainsString('aria-label="Bez szeryfów — czcionka bezszeryfowa"', $panel);
        $this->assertStringContainsString('aria-label="Szarość — skala szarości"', $panel);

        $faCss = base_path('node_modules/@fortawesome/fontawesome-free/css/all.min.css');
        if (! is_file($faCss)) {
            return; // bez node_modules (np. sam PHP na serwerze) sprawdzamy tylko markup
        }
        $css = file_get_contents($faCss);
        preg_match_all('/fa-solid fa-([a-z0-9-]+)/', $panel, $m);
        foreach (array_unique($m[1]) as $icon) {
            $this->assertMatchesRegularExpression('/\.fa-' . preg_quote($icon, '/') . '[,{]/', $css, "ikona fa-{$icon} istnieje w Font Awesome");
        }
    }

    public function test_szerokosc_jak_naglowek_i_wlasna_w_bu2(): void
    {
        $this->panel('feer');
        $this->assertStringContainsString('.a11yb-in { max-width: var(--a11y-bar-w, 72rem);', $this->get('/')->getContent());

        $this->panel('bu2');
        $this->assertStringContainsString('#a11y-panel { --a11y-bar-w: 1200px; }', $this->get('/')->getContent());
    }
}
