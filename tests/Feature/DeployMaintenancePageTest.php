<?php

namespace Tests\Feature;

use App\Support\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Strona 503 pokazywana podczas ./deploy.sh (artisan down --render="errors::503"): okienko w stylu stron serwisowych
 * („Przerwa techniczna”, „Witryna w budowie”), nadal samodzielne (bez zasobów z zewnątrz), z odliczaniem zgodnym z paskiem
 * również przy ograniczonym ruchu i z kontrastem ≥ 4,5:1.
 */
class DeployMaintenancePageTest extends TestCase
{
    use RefreshDatabase;

    private function html(): string
    {
        return view('errors.503')->render();
    }

    private function cssColor(string $html, string $variable): string
    {
        $this->assertSame(1, preg_match('/'.preg_quote($variable, '/').':\s*(#[0-9a-fA-F]{6})/', $html, $m), "Brak zmiennej $variable na stronie 503");

        return $m[1];
    }

    public function test_page_keeps_message_refresh_and_status_region(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('Wgrywamy nową wersję portalu', $html);
        $this->assertStringContainsString('Za kilkanaście sekund wszystko będzie działać. Strona odświeży się sama. Nie musisz nic robić.', $html);
        $this->assertStringContainsString('<meta http-equiv="refresh" content="15">', $html);
        $this->assertStringContainsString('<div role="status">', $html);
        $this->assertStringContainsString('noindex', $html);
        $this->assertStringContainsString('Odśwież teraz', $html);
        $this->assertStringContainsString('<html lang="pl">', $html);
    }

    public function test_page_looks_like_the_other_service_pages(): void
    {
        $html = $this->html();

        // Okienko: wyśrodkowana karta z obramowaniem, zaokrągleniem i cieniem jak „Przerwa techniczna”.
        $this->assertStringContainsString('border-radius: 1rem', $html);
        $this->assertStringContainsString('box-shadow: 0 10px 40px rgba(0, 0, 0, .06)', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('linear-gradient(135deg, var(--brand-light), #fff)', $html);
        // Okrągła ikona i ilustrowane tło (z własnym podpisem zamiast „w budowie”).
        $this->assertStringContainsString('width: 4.5rem; height: 4.5rem', $html);
        $this->assertStringContainsString('border-radius: 9999px', $html);
        $this->assertStringContainsString('cb-backdrop', $html);
        $this->assertStringContainsString('>wgrywamy zmiany</text>', $html);
        $this->assertStringNotContainsString('>w budowie</text>', $html);
        // Karta leży nad tłem (reguła z partiala dotyczy bezpośredniego dziecka <body>).
        $this->assertMatchesRegularExpression('/<body>.*<main>/s', $html);
    }

    public function test_backdrop_label_defaults_to_under_construction_for_other_pages(): void
    {
        $this->assertStringContainsString('>w budowie</text>', view('errors.partials.construction-backdrop')->render());
        $this->assertStringContainsString('>inny podpis</text>', view('errors.partials.construction-backdrop', ['backdropLabel' => 'inny podpis'])->render());
    }

    public function test_page_stays_self_contained(): void
    {
        $html = $this->html();

        // Musi działać w trakcie aktualizacji: bez arkuszy, czcionek, ikon i skryptów z zewnątrz.
        $this->assertStringNotContainsString('<link', $html);
        $this->assertStringNotContainsString('https://', $html);
        $this->assertDoesNotMatchRegularExpression('/\bsrc\s*=\s*["\']?(https?:)?\/\//i', $html);
        $this->assertStringNotContainsString('@vite', $html);
    }

    public function test_progress_bar_follows_the_countdown_also_with_reduced_motion(): void
    {
        $html = $this->html();

        // Animacja CSS tylko bez ograniczenia ruchu; przy ograniczonym pasek przesuwa skrypt razem z odliczaniem.
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce) { .icon svg, .progress span { animation: none; } }', $html);
        $this->assertStringNotContainsString('.progress span { width: 100%; }', $html);
        $this->assertStringContainsString("bar = document.querySelector('.progress span')", $html);
        $this->assertStringContainsString("bar.style.width = Math.min(100, (total - Math.max(left, 0)) / total * 100) + '%';", $html);
        $this->assertStringContainsString('if (left <= 0) { clearInterval(t); location.reload(); }', $html);
        // Odliczanie i pasek są dekoracyjne dla czytników — komunikat niesie region role="status".
        $this->assertStringContainsString('<div class="progress" aria-hidden="true">', $html);
        $this->assertStringContainsString('<p class="meta" aria-hidden="true">', $html);
    }

    public function test_colors_keep_the_contrast_requirement(): void
    {
        $html = $this->html();
        $brand = $this->cssColor($html, '--brand');
        $light = $this->cssColor($html, '--brand-light');
        $muted = $this->cssColor($html, '--muted');
        $ink = $this->cssColor($html, '--ink');

        $this->assertGreaterThanOrEqual(7.0, Color::contrast('#ffffff', $brand), 'Biały napis na przycisku');
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($brand, $light), 'Ikona na jasnym kółku');
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($muted, '#ffffff'), 'Podpis i odliczanie na karcie');
        $this->assertGreaterThanOrEqual(7.0, Color::contrast($ink, '#ffffff'), 'Treść na karcie');
        // Najechany przycisk: biały na kolorze tekstu (ciemny).
        $this->assertGreaterThanOrEqual(7.0, Color::contrast('#ffffff', $ink));
    }
}
