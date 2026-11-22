<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Szablon „kreatywni”: aktualności tuż pod sliderem, nad „W czym pomagamy” — najnowsza wyróżniona (zdjęcie, kafelek
 * daty), dwie kolejne w zwartych wierszach, jedna płaska lista.
 */
class KreatywniNewsSectionTest extends TestCase
{
    use RefreshDatabase;

    private function useTemplate(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => 'kreatywni'])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function news(string $title, int $daysAgo): News
    {
        return News::create(['title' => $title, 'slug' => Str::slug($title), 'content' => '<p>x</p>', 'excerpt' => 'Zajawka: '.$title,
            'published_at' => now()->subDays($daysAgo), 'is_published' => true]);
    }

    public function test_aktualnosci_sa_pod_sliderem_nad_obszarami_dzialania(): void
    {
        $this->useTemplate();
        $this->news('Starsza wiadomość', 5);
        $this->news('Środkowa wiadomość', 3);
        $newest = $this->news('Najnowsza wiadomość', 1);

        $html = $this->get('/')->assertOk()->getContent();

        $hero = strpos($html, 'class="kc-hero"');
        $news = strpos($html, 'id="kc-news-title"');
        $mission = strpos($html, 'id="kc-mission-title"');
        $this->assertNotFalse($news);
        $this->assertTrue($hero < $news && $news < $mission, 'aktualności między sliderem a „W czym pomagamy”');

        $this->assertStringContainsString('<p class="kc-eyebrow kc-hand">Co nowego?</p>', $html);
        $this->assertStringContainsString('kc-news-layout has-rest has-two', $html);
        // Najnowsza wyróżniona: kafelek daty z pełną datą dla czytników, zajawka.
        $this->assertMatchesRegularExpression('#<li class="kc-news-main">\s*<a href="[^"]*'.$newest->slug.'" class="kc-news-feature">#', $html);
        $this->assertStringContainsString('<span class="kc-sr">'.$newest->published_at->translatedFormat('j F Y').'</span>', $html);
        $this->assertStringContainsString('Zajawka: Najnowsza wiadomość', $html);
        // Dwie kolejne jako wiersze, bez zajawek, w tej samej liście.
        $this->assertSame(2, substr_count($html, 'class="kc-news-row"'));
        $this->assertStringNotContainsString('Zajawka: Starsza wiadomość', $html);
        $this->assertLessThan(strpos($html, 'Starsza wiadomość'), strpos($html, 'Środkowa wiadomość'));
        $this->assertStringContainsString('Wszystkie aktualności', $html);
    }

    public function test_jedna_aktualnosc_bez_kolumny_wierszy(): void
    {
        $this->useTemplate();
        $this->news('Jedyna wiadomość', 1);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<ul class="kc-news-layout">', $html);
        $this->assertSame(0, substr_count($html, 'class="kc-news-row"'));
    }
}
