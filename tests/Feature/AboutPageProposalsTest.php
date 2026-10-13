<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona „O organizacji”: misja z przyciskami, statystyki z datą, skrócona historia, media z datą, dane organizacji, „Jak możesz pomóc”. */
class AboutPageProposalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_shows_new_sections(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update(['krs_number' => '0000123456', 'nip_number' => '1234567890']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $timeline = collect(range(2010, 2016))->map(fn ($y) => ['year' => (string) $y, 'text' => 'Wydarzenie '.$y])->all();
        $page = Page::create([
            'title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_intro' => 'Wstęp.',
            'about_stats' => [['value' => '500', 'label' => 'osób', 'icon' => 'bi-people']],
            'about_timeline' => $timeline,
            'about_press' => [['url' => 'https://example.org/artykul', 'title' => 'Artykuł o nas', 'source' => 'Gazeta', 'image' => '', 'date' => '2025-03-14']],
            'about_documents_intro' => 'Dokumenty.',
            'layout_data' => ['about_mission' => 'Pomagamy korzystać z cyfrowego świata.', 'about_stats_asof' => 'grudzień 2025'],
        ]);

        $html = $this->get($page->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('Pomagamy korzystać z cyfrowego świata.', $html);
        $this->assertStringContainsString('Stan na: grudzień 2025', $html);
        $this->assertStringContainsString('Cała historia (+2)', $html);
        $this->assertStringContainsString('14 marca 2025', $html);
        $this->assertStringContainsString('Dane organizacji', $html);
        $this->assertStringContainsString('0000123456', $html);
        $this->assertStringContainsString('Jak możesz pomóc', $html);
        $this->assertStringNotContainsString('1,5% podatku', $html, 'Bez statusu OPP karty 1,5% nie ma.');
    }
}
