<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/** Wpisy Wiem FEER w wyszukiwarce serwisu (/szukaj) — grupa „Wiem FEER” z podświetleniem frazy. */
class BlogSearchTest extends BlogTestCase
{
    public function test_wyszukiwarka_pokazuje_grupe_wiem_feer_z_podswietleniem(): void
    {
        $this->article('Dostępność cyfrowa w praktyce', [
            'excerpt' => 'Krótko o "WCAG" & dostępności.',
            'published_at' => now()->subDays(2),
        ]);
        $this->article('Inny wpis', ['body' => '<p>Długi wstęp. ' . str_repeat('Lorem ipsum. ', 30) . 'Tu jest słowo kluczowe dostępności w treści.</p>']);
        $this->article('Szkic o dostępności', ['is_published' => false]);
        $this->article('Wyłączony o dostępności', ['is_disabled' => true]);
        $this->article('Zaplanowany o dostępności', ['published_at' => now()->addDay()]);

        $html = $this->get(route('search', ['q' => 'dostępności']))->assertOk()->getContent();

        $this->assertStringContainsString('Wiem FEER', $html);
        $this->assertStringNotContainsString('Wiem FEER (blog)', $html);
        $this->assertStringContainsString('href="' . route('blog.show', 'dostepnosc-cyfrowa-w-praktyce') . '"', $html);
        // Zajawka escapowana, fraza w <mark>.
        $this->assertStringContainsString('Krótko o &quot;WCAG&quot; &amp; <mark>dostępności</mark>.', $html);
        // Fragment wokół trafienia w treści (nie początek treści).
        $this->assertStringContainsString('słowo kluczowe <mark>dostępności</mark> w treści', $html);
        $this->assertStringNotContainsString('Szkic o dostępności', $html);
        $this->assertStringNotContainsString('Wyłączony o dostępności', $html);
        $this->assertStringNotContainsString('Zaplanowany o dostępności', $html);
    }

    public function test_fraza_nie_psuje_encji_html(): void
    {
        $this->article('Tom & Jerry', ['excerpt' => 'Kot & mysz amp']);

        $html = $this->get('/szukaj?q=amp')->assertOk()->getContent();

        $this->assertStringContainsString('Kot &amp; mysz <mark>amp</mark>', $html);
    }

    public function test_brak_bazy_bloga_nie_psuje_wyszukiwarki(): void
    {
        config(['database.connections.blog.database' => '/nonexistent-dir/blog.sqlite']);
        DB::purge('blog');

        $this->get('/szukaj?q=cokolwiek')->assertOk()->assertDontSee('<mark>', false);
    }
}
