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
        // Równe karty po dwie w rzędzie, od najnowszej: kafelek daty z pełną datą dla czytników i zajawka w każdej.
        $this->assertStringContainsString('<ul class="kc-news-layout">', $html);
        $this->assertSame(3, substr_count($html, '<li class="kc-news-item">'));
        $this->assertMatchesRegularExpression('#<li class="kc-news-item">\s*<a href="[^"]*'.$newest->slug.'" class="kc-news-feature">#', $html);
        $this->assertStringContainsString('<span class="kc-sr">'.$newest->published_at->translatedFormat('j F Y').'</span>', $html);
        $this->assertStringContainsString('Zajawka: Najnowsza wiadomość', $html);
        $this->assertStringContainsString('Zajawka: Starsza wiadomość', $html);
        $this->assertLessThan(strpos($html, 'Środkowa wiadomość'), strpos($html, 'Najnowsza wiadomość'));
        $this->assertLessThan(strpos($html, 'Starsza wiadomość'), strpos($html, 'Środkowa wiadomość'));
        $this->assertStringContainsString('Wszystkie aktualności', $html);
    }

    public function test_cztery_najnowsze_po_dwie_w_rzedzie(): void
    {
        $this->useTemplate();
        foreach (range(1, 5) as $i) {
            $this->news('Wiadomość '.$i, $i);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, '<li class="kc-news-item">'), 'cztery najnowsze = dwa pełne rzędy');
        $this->assertStringNotContainsString('Wiadomość 5', $html);
        $this->assertStringContainsString('.kc-news-layout { grid-template-columns: repeat(2, minmax(0, 1fr))', $html);
    }

    public function test_jedna_aktualnosc_to_jedna_karta(): void
    {
        $this->useTemplate();
        $this->news('Jedyna wiadomość', 1);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<ul class="kc-news-layout">', $html);
        $this->assertSame(1, substr_count($html, '<li class="kc-news-item">'));
    }
}
