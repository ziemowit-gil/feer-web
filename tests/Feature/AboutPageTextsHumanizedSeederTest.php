<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\AboutPageTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    private function about(array $extra = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Fundacja FEER', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true,
        ], $extra));
    }

    public function test_poprawia_teksty_i_jest_idempotentny(): void
    {
        $page = $this->about([
            'about_intro' => 'Świat staje się dostępny wtedy, gdy przestajemy się go bać, bo największe bariery nie tkwią w murach, lecz w braku wzajemnego zrozumienia. Reszta.',
            'about_stats' => [['value' => 'więcej jak 1000', 'label' => 'dzieciaków które poznały niepełnosprawność']],
            'about_timeline' => [
                ['year' => '2021-2022', 'text' => 'Nasza dzialalność zwalnia, mierzymy się z problemami.', 'url' => 'https://example.org'],
                ['year' => '2020', 'text' => 'Tekst bez zmian'],
            ],
        ]);

        $this->seed(AboutPageTextsHumanizedSeeder::class);
        $this->seed(AboutPageTextsHumanizedSeeder::class);

        $page->refresh();
        $this->assertStringStartsWith('Świat staje się dostępny, gdy przestajemy się go bać.', $page->about_intro);
        $this->assertStringNotContainsString('lecz', $page->about_intro);
        $this->assertSame('ponad 1000', $page->about_stats[0]['value']);
        $this->assertSame('dzieci na warsztatach o niepełnosprawności', $page->about_stats[0]['label']);
        $this->assertSame('Nasza działalność zwalnia, mierzymy się z problemami.', $page->about_timeline[0]['text']);
        $this->assertSame('https://example.org', $page->about_timeline[0]['url'], 'pozostałe pola wpisu zostają');
        $this->assertSame('Tekst bez zmian', $page->about_timeline[1]['text']);
    }

    public function test_nie_nadpisuje_tekstow_zmienionych_w_panelu(): void
    {
        $page = $this->about(['about_intro' => 'Własny wstęp redakcji.', 'about_documents_intro' => 'Własny opis dokumentów.']);

        $this->seed(AboutPageTextsHumanizedSeeder::class);

        $this->assertSame('Własny wstęp redakcji.', $page->fresh()->about_intro);
        $this->assertSame('Własny opis dokumentów.', $page->fresh()->about_documents_intro);
    }
}
