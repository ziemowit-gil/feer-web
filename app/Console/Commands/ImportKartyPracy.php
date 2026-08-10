<?php

namespace App\Console\Commands;

use App\Models\EducationalMaterial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportKartyPracy extends Command
{
    protected $signature = 'import:karty-pracy
                                {--dry-run : Tylko podgląd — nie zapisuj rekordów}
                                {--skip-files : Nie pobieraj plików PDF (tylko metadane)}
                                {--force : Nadpisz rekordy o tym samym tytule}';

    protected $description = 'Importuje 12 kart pracy z feer-demo.2clicks.pl do EducationalMaterials';

    private const UA          = 'Mozilla/5.0 (compatible; FEER-Importer/1.0; +https://feer.org.pl)';
    private const SOURCE_BASE = 'https://feer-demo.2clicks.pl';

    /**
     * Znane karty — tytuły i URL podstron źródłowych.
     * PDF URL jest pobierany dynamicznie z każdej podstrony.
     */
    private const CARDS = [
        [
            'title'        => 'Mini komiks "Autyzm" — zrozumieć i wspierać',
            'source_url'   => '/aktualnosci/karty-pracy/mini-komiks-autyzm-zrozumiec-i-wspierac.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Bezpłatna prezentacja: Choroba Alzheimera',
            'source_url'   => '/aktualnosci/karty-pracy/bezplatna-prezentacja-choroba-alzheimera.html',
            'target_group' => 'Szkoła podstawowa, nauczyciele',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Bezpłatna prezentacja: Cukrzyca',
            'source_url'   => '/aktualnosci/karty-pracy/bezplatna-prezentacja-cukrzyca.html',
            'target_group' => 'Szkoła podstawowa, nauczyciele',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Dyslektyk czyta',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-dyslektyk-czyta.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Uczeń z niepełnosprawnością w klasie',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-uczen-z-niepelnosprawnoscia-w-klasie.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Savoir-vivre w podróży — jak pomóc osobie z niepełnosprawnością podczas wakacji',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-sovoir-vivre-w-podrozy-jak-niepelnosprawnoscia-podczas-wakacji.html',
            'target_group' => 'Szkoła podstawowa, klasy 4–8',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Komunikacja z osobą z ASD',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-komunikacja-z-osoba-z-asd.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Migowe kodowanie',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-migowe-kodowanie.html',
            'target_group' => 'Szkoła podstawowa, klasy 1–3',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Jak zachować się w towarzystwie osoby niewidomej?',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-jak-zachowac-sie-w-towarzystwie-niewidomego.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Pies przewodnik',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-pies-przewodnik.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: Osoby z dysfunkcją wzroku',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-osoby-z-dysfunkcja-wzroku.html',
            'target_group' => 'Szkoła podstawowa',
            'type'         => 'pdf',
        ],
        [
            'title'        => 'Karta pracy: 10 października — Światowy Dzień Zdrowia Psychicznego',
            'source_url'   => '/aktualnosci/karty-pracy/karta-pracy-swiatowy-dzien-zdrowia-psychicznego.html',
            'target_group' => 'Szkoła podstawowa, klasy 4–8',
            'type'         => 'pdf',
        ],
    ];

    public function handle(): int
    {
        $isDry     = $this->option('dry-run');
        $skipFiles = $this->option('skip-files');
        $force     = $this->option('force');
        $canFetch  = $this->checkConnectivity();

        if (! $canFetch) {
            $this->warn('⚠ Brak połączenia z feer-demo.2clicks.pl — opisy i pliki PDF nie będą pobrane.');
            $this->warn('  Uruchom polecenie na serwerze produkcyjnym, aby pobrać pliki.');
        }

        $imported = 0;
        $skipped  = 0;

        foreach (self::CARDS as $i => $card) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, count(self::CARDS), $card['title']));

            $existing = EducationalMaterial::where('title', $card['title'])->first();
            if ($existing && ! $force) {
                $this->warn('  → już istnieje, pomijam. (użyj --force aby nadpisać)');
                $skipped++;
                continue;
            }

            $detail = $canFetch ? $this->fetchDetail(self::SOURCE_BASE . $card['source_url']) : [];
            $desc   = $detail['description'] ?? '';
            $pdfUrl = $detail['pdf_url'] ?? null;

            $this->line('  Opis: ' . ($desc ? mb_substr($desc, 0, 80) . '…' : '— brak'));
            $this->line('  PDF:  ' . ($pdfUrl ?: '— niedostępny'));

            if ($isDry) {
                $this->comment('  [dry-run] pominięto zapis.');
                continue;
            }

            if ($existing && $force) {
                $material = $existing;
                $material->update([
                    'description'  => $desc ?: $existing->description,
                    'target_group' => $card['target_group'],
                    'type'         => $card['type'],
                    'order'        => $i,
                ]);
            } else {
                $material = EducationalMaterial::create([
                    'title'        => $card['title'],
                    'description'  => $desc ?: 'Karta pracy — materiał edukacyjny FEER.',
                    'target_group' => $card['target_group'],
                    'type'         => $card['type'],
                    'order'        => $i,
                    'is_published' => true,
                ]);
            }

            if ($pdfUrl && ! $skipFiles && ! $material->getFirstMedia('file')) {
                $this->line('  Pobieranie PDF…');
                try {
                    $material->addMediaFromUrl($pdfUrl)->toMediaCollection('file');
                    $this->info('  → PDF dołączony.');
                } catch (\Throwable $e) {
                    $this->warn('  → Nie udało się pobrać PDF: ' . $e->getMessage());
                }
            }

            $imported++;
        }

        $this->newLine();
        $this->info(sprintf('Gotowe. Zaimportowano/zaktualizowano: %d, pominięto: %d.', $imported, $skipped));

        return 0;
    }

    private function checkConnectivity(): bool
    {
        try {
            $r = Http::timeout(5)->withHeaders(['User-Agent' => self::UA])->head(self::SOURCE_BASE);
            return $r->status() < 500;
        } catch (\Throwable) {
            return false;
        }
    }

    private function fetchDetail(string $url): array
    {
        try {
            $response = Http::timeout(20)->withHeaders(['User-Agent' => self::UA])->get($url);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->ok()) {
            return [];
        }

        $html = $response->body();
        $dom  = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // PDF link
        $pdfUrl = null;
        $links  = $xpath->query('//a[contains(@href,".pdf")]');
        if ($links->length > 0) {
            $href = $links->item(0)->getAttribute('href');
            $pdfUrl = str_starts_with($href, 'http') ? $href : self::SOURCE_BASE . '/' . ltrim($href, '/');
        }

        // Opis — akapity w treści artykułu
        $desc        = '';
        $paragraphs  = $xpath->query('//p');
        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);
            if (strlen($text) > 30 && ! str_contains($text, 'Kliknij') && ! str_contains($text, 'cookie')) {
                $desc .= $text . ' ';
                if (strlen($desc) > 500) {
                    break;
                }
            }
        }

        return ['pdf_url' => $pdfUrl, 'description' => trim($desc)];
    }
}
