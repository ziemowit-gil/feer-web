<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PagesDashesHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesDashesHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $slug, string $content, array $extra = []): Page
    {
        return Page::withoutGlobalScopes()->updateOrCreate(['slug' => $slug], $extra + ['title' => $slug, 'type' => 'standard', 'is_published' => true, 'content' => $content]);
    }

    public function test_poprawia_listy_etykiet_zdania_i_tytul_oraz_jest_idempotentny(): void
    {
        $zakres = $this->page('dostepnosc-w-praktyce-zakres', '<ul><li><strong>Tajemniczy klient</strong> — Przeprowadzamy audyty.</li><li><strong>Rzetelne doradztwo</strong> — Oparte na zaufaniu.</li></ul>');
        $perspektywa = $this->page('z-mojej-perspektywy-przyjdz-i-po-prostu-porozmawiaj',
            '<p>To nie kolejna konferencja dla wtajemniczonych &mdash; to wydarzenie dla każdego.</p><p>Czas się skończy &mdash; to w porządku.</p><p>godziny 11:00&ndash;14:00</p>',
            ['title' => '„Z Mojej Perspektywy…" — przyjdź i po prostu porozmawiaj']);
        $own = $this->page('dostepnosc-w-praktyce-zasady', '<p>Własny tekst redakcji — bez zmian.</p>');

        $this->seed(PagesDashesHumanizedSeeder::class);
        $this->seed(PagesDashesHumanizedSeeder::class);

        $this->assertSame('<ul><li><strong>Tajemniczy klient</strong>. Przeprowadzamy audyty.</li><li><strong>Rzetelne doradztwo</strong>. Oparte na zaufaniu.</li></ul>', $zakres->fresh()->content);
        $this->assertStringContainsString('<p>To wydarzenie dla każdego.</p>', $perspektywa->fresh()->content);
        $this->assertStringContainsString('Czas się skończy: to w porządku.', $perspektywa->fresh()->content);
        $this->assertStringContainsString('11:00&ndash;14:00', $perspektywa->fresh()->content, 'zakres godzin zostaje');
        $this->assertSame('„Z Mojej Perspektywy…”: przyjdź i po prostu porozmawiaj', $perspektywa->fresh()->title);
        $this->assertStringContainsString('Własny tekst redakcji — bez zmian.', $own->fresh()->content);
    }

    public function test_poprawia_podpis_zdjecia_na_stronie_z_blokami(): void
    {
        $strony = $this->page('wecms-funkcje-strony', '<p>stare</p>', ['blocks' => [
            ['type' => 'html', 'attrs' => ['html' => '<figure><figcaption>Drzewo stron — przeciąganie, podstrony</figcaption></figure>']],
        ]]);

        $this->seed(PagesDashesHumanizedSeeder::class);

        $this->assertStringContainsString('Drzewo stron: przeciąganie, podstrony', $strony->fresh()->content);
    }
}
