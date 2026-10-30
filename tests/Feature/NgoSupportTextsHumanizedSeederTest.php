<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\NgoSupportTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NgoSupportTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_poprawia_bloki_i_tresc_podstrony_oraz_jest_idempotentny(): void
    {
        $page = Page::create([
            'title' => 'Ile to kosztuje?', 'slug' => 'wsparcie-ngo-ile-to-kosztuje', 'type' => 'standard', 'is_published' => true,
            'blocks' => [
                ['type' => 'paragraph', 'attrs' => ['text' => 'Wierzymy w przejrzystość relacji. Dlatego nasze wsparcie techniczne opiera się na trzech zasadach:', 'lead' => true]],
                ['type' => 'html', 'attrs' => ['html' => '<p>Żadnych ukrytych opłat, żadnych dodatkowych wydatków.</p>']],
            ],
            'content' => '<p>stare</p>',
        ]);

        $this->seed(NgoSupportTextsHumanizedSeeder::class);
        $this->seed(NgoSupportTextsHumanizedSeeder::class);

        $page->refresh();
        $this->assertSame('Nasze wsparcie techniczne opiera się na trzech zasadach:', $page->blocks[0]['attrs']['text']);
        $this->assertStringContainsString('Nasze wsparcie techniczne', $page->content);
        $this->assertStringNotContainsString('Wierzymy', $page->content);
        $this->assertStringContainsString('Bez ukrytych opłat i dodatkowych wydatków.', $page->content);
    }

    public function test_poprawia_podstrone_bez_blokow(): void
    {
        $page = Page::create([
            'title' => 'Najczęstsze pytania', 'slug' => 'wsparcie-ngo-najczestsze-pytania', 'type' => 'standard', 'is_published' => true,
            'content' => '<p>Nie. Wystarczy, że opiszesz, co nie działa albo co chcecie zmienić — własnymi słowami. Resztę dopytamy w rozmowie.</p><p>Inne zdanie.</p>',
        ]);

        $this->seed(NgoSupportTextsHumanizedSeeder::class);

        $this->assertStringContainsString('Nie. Opisz własnymi słowami', $page->fresh()->content);
        $this->assertStringContainsString('Inne zdanie.', $page->fresh()->content);
    }

    public function test_nie_nadpisuje_zmienionych_tekstow(): void
    {
        $page = Page::create([
            'title' => 'Kontakt', 'slug' => 'wsparcie-ngo-kontakt', 'type' => 'standard', 'is_published' => true,
            'content' => '<p>Własny tekst.</p>',
        ]);

        $this->seed(NgoSupportTextsHumanizedSeeder::class);

        $this->assertSame('<p>Własny tekst.</p>', $page->fresh()->content);
    }
}
