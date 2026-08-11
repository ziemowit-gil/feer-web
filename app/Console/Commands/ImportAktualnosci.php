<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportAktualnosci extends Command
{
    protected $signature = 'import:aktualnosci
                                {--dry-run : Tylko podgląd — nie zapisuj rekordów}
                                {--skip-images : Nie pobieraj zdjęć (tylko metadane)}
                                {--force : Nadpisz rekordy o tym samym slugu}';

    protected $description = 'Importuje aktualności z feer-demo.2clicks.pl do News; starsze niż 1 rok oznacza jako archiwalne';

    private const UA          = 'Mozilla/5.0 (compatible; FEER-Importer/1.0; +https://feer.org.pl)';
    private const SOURCE_BASE = 'https://feer-demo.2clicks.pl';
    private const RSS_URL     = 'https://feer-demo.2clicks.pl/rss/aktualnosci_pl.xml?all=true';

    public function handle(): int
    {
        $isDry      = $this->option('dry-run');
        $skipImages = $this->option('skip-images');
        $force      = $this->option('force');
        $archiveAt  = now()->subYear();

        $canFetch = $this->checkConnectivity();
        if (! $canFetch) {
            $this->error('Brak połączenia z feer-demo.2clicks.pl. Uruchom polecenie na serwerze produkcyjnym.');
            return 1;
        }

        $this->line('Pobieranie kanału RSS…');
        $rss = $this->fetchRss();
        if (empty($rss)) {
            $this->error('Nie udało się pobrać/sparsować RSS. Przerywam.');
            return 1;
        }

        $category = $this->findOrCreateCategory($isDry);
        $this->info(sprintf('Kategoria: %s (id=%s)', $category->name, $category->id ?? '—'));

        $imported = 0;
        $skipped  = 0;
        $total    = count($rss);

        foreach ($rss as $i => $item) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, $total, mb_substr($item['title'], 0, 70)));

            $slug = $this->slugFromLink($item['link']);

            $existing = News::withTrashed()->where('slug', $slug)->first();
            if ($existing && ! $force) {
                $this->warn('  → już istnieje, pomijam. (użyj --force aby nadpisać)');
                $skipped++;
                continue;
            }

            $publishedAt = $this->parseDate($item['pubDate']);
            $isArchived  = $publishedAt && $publishedAt->lessThan($archiveAt);
            $content     = $item['content'];
            $excerpt     = $this->makeExcerpt($content);

            $this->line(sprintf('  Data: %s | %s',
                $publishedAt?->format('Y-m-d') ?? '?',
                $isArchived ? 'ARCHIWALNE' : 'bieżące'
            ));

            if ($isDry) {
                $this->comment('  [dry-run] pominięto zapis.');
                continue;
            }

            $data = [
                'news_category_id' => $category->id,
                'title'            => $item['title'],
                'slug'             => $slug,
                'excerpt'          => $excerpt,
                'content'          => $content,
                'published_at'     => $publishedAt,
                'is_published'     => true,
                'is_archived'      => $isArchived,
                'is_legacy'        => true,
            ];

            if ($existing && $force) {
                $existing->update($data);
                $news = $existing->fresh();
                $this->line('  → zaktualizowano.');
            } else {
                $news = News::create($data);
                $this->line('  → zapisano.');
            }

            if ($item['image_url'] && ! $skipImages && ! $news->getFirstMedia('image')) {
                $this->line('  Pobieranie zdjęcia…');
                try {
                    $news->addMediaFromUrl($item['image_url'])->toMediaCollection('image');
                    $this->info('  → zdjęcie dołączone.');
                } catch (\Throwable $e) {
                    $this->warn('  → Nie udało się pobrać zdjęcia: ' . $e->getMessage());
                }
            }

            $imported++;
        }

        $this->newLine();
        $this->info(sprintf('Gotowe. Zaimportowano/zaktualizowano: %d, pominięto: %d z %d.', $imported, $skipped, $total));
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

    private function fetchRss(): array
    {
        try {
            $response = Http::timeout(30)->withHeaders(['User-Agent' => self::UA])->get(self::RSS_URL);
        } catch (\Throwable $e) {
            $this->warn('RSS fetch error: ' . $e->getMessage());
            return [];
        }

        if (! $response->ok()) {
            $this->warn('RSS HTTP ' . $response->status());
            return [];
        }

        $xml = $response->body();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Mapa link → imageUrl z niestandardowych bloków <image> w RSS
        $imageMap = [];
        foreach ($xpath->query('//channel/image') as $imgNode) {
            $link = trim($xpath->evaluate('string(link)', $imgNode));
            $url  = trim($xpath->evaluate('string(url)', $imgNode));
            if ($link && $url) {
                $imageMap[$link] = str_starts_with($url, 'http') ? $url : self::SOURCE_BASE . '/' . ltrim($url, '/');
            }
        }

        $items = [];
        foreach ($xpath->query('//channel/item') as $itemNode) {
            $title   = trim($xpath->evaluate('string(title)', $itemNode));
            $link    = trim($xpath->evaluate('string(link)', $itemNode));
            $pubDate = trim($xpath->evaluate('string(pubDate)', $itemNode));
            $desc    = trim($xpath->evaluate('string(description)', $itemNode));

            if (! $title || ! $link) {
                continue;
            }

            $items[] = [
                'title'     => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'link'      => $link,
                'pubDate'   => $pubDate,
                'content'   => $desc ?: '',
                'image_url' => $imageMap[$link] ?? null,
            ];
        }

        return $items;
    }

    private function slugFromLink(string $link): string
    {
        // "aktualnosci/komunikat-zmiana-adresu-do-e-doreczen.html" → "komunikat-zmiana-adresu-do-e-doreczen"
        $path = ltrim($link, '/');
        $path = preg_replace('#^aktualnosci/#', '', $path);
        $path = preg_replace('#\.html$#', '', $path);
        // Zabezpieczenie na wypadek ukraińskich/cyrylickich slugów
        return Str::slug($path) ?: Str::slug($path, '-', 'pl');
    }

    private function parseDate(string $pubDate): ?\Illuminate\Support\Carbon
    {
        try {
            return \Illuminate\Support\Carbon::parse($pubDate);
        } catch (\Throwable) {
            return null;
        }
    }

    private function makeExcerpt(string $html): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return mb_substr($text, 0, 200);
    }

    private function findOrCreateCategory(bool $isDry): NewsCategory
    {
        $cat = NewsCategory::where('slug', 'archiwum')->first();

        if ($cat) {
            return $cat;
        }

        if ($isDry) {
            return new NewsCategory(['id' => null, 'name' => 'Archiwum', 'slug' => 'archiwum']);
        }

        return NewsCategory::create([
            'name'  => 'Archiwum',
            'slug'  => 'archiwum',
            'order' => 99,
        ]);
    }
}
