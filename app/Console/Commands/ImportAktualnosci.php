<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Redirect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportAktualnosci extends Command
{
    protected $signature = 'import:aktualnosci
                                {--dry-run : Tylko podgląd — nie zapisuj rekordów}
                                {--skip-images : Nie pobieraj zdjęć (tylko metadane)}
                                {--force : Nadpisz rekordy o tym samym slugu}';

    protected $description = 'Importuje aktualności z feer-demo.2clicks.pl; starsze niż 1 rok oznacza jako archiwalne';

    private const UA          = 'Mozilla/5.0 (compatible; FEER-Importer/1.0; +https://feer.org.pl)';
    private const SOURCE_BASE = 'https://feer-demo.2clicks.pl';
    private const RSS_URL     = 'https://feer-demo.2clicks.pl/rss/aktualnosci_pl.xml?all=true';

    /**
     * Kategorie tworzone automatycznie (name → slug).
     * Kolejność w tablicy CATEGORIES_MAP decyduje o priorytecie dopasowania.
     */
    private const CATEGORY_DEFS = [
        'materialy-edukacyjne'  => 'Materiały edukacyjne',
        'webinary-i-szkolenia'  => 'Webinary i szkolenia',
        'warsztaty'             => 'Warsztaty',
        'dla-ngo-i-biznesu'     => 'Dla NGO i biznesu',
        'komunikaty'            => 'Komunikaty',
        'ogolne'                => 'Ogólne',
    ];

    /** Słowa kluczowe tytułu/treści → slug kategorii (pierwsza pasująca wygrywa). */
    private const CATEGORIES_MAP = [
        'materialy-edukacyjne' => [
            'karta pracy', 'karty pracy', 'bezpłatna prezentacja', 'bezpłatne karty',
            'materiał edukacyjny', 'materiały edukacyjne', 'mini komiks',
            'ciekawy artykuł',
        ],
        'webinary-i-szkolenia' => [
            'webinar', 'szkolenie', 'bezpłatne szkolenie',
        ],
        'warsztaty' => [
            'warsztaty', 'warsztat',
        ],
        'dla-ngo-i-biznesu' => [
            'ngo', 'dla organizacji', 'canva', 'gtd', 'cyfrowe', 'kompetencji it',
        ],
        'komunikaty' => [
            'komunikat', 'zmiana godzin', 'godziny pracy', 'nieczynny', 'nieczynna',
            'nie pracujemy', 'przerwa wakacyjna', 'zmiana numeru', 'harmonogram',
            'odwołanie', 'zmiana', 'poszukujemy', 'dni wolne',
        ],
    ];

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

        $categories = $isDry ? [] : $this->ensureCategories();

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

            $publishedAt  = $this->parseDate($item['pubDate']);
            $isArchived   = $publishedAt && $publishedAt->lessThan($archiveAt);
            $content      = $item['content'];
            $excerpt      = $this->makeExcerpt($content);
            $categorySlug = $this->classifyArticle($item['title'], $content);
            $categoryId   = $categories[$categorySlug] ?? null;

            $this->line(sprintf('  Data: %s | %s | Kategoria: %s',
                $publishedAt?->format('Y-m-d') ?? '?',
                $isArchived ? 'ARCHIWALNE' : 'bieżące',
                $categorySlug
            ));

            if ($isDry) {
                $this->comment('  [dry-run] pominięto zapis.');
                continue;
            }

            $data = [
                'news_category_id' => $categoryId,
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

            // Przekierowanie: /aktualnosci/stary-slug.html → /aktualnosci/nowy-slug
            $oldPath = '/aktualnosci/' . ltrim(preg_replace('#^aktualnosci/#', '', $item['link']), '/');
            $newUrl  = route('news.show', $news);
            if ($oldPath !== Redirect::normalizePath($newUrl)) {
                Redirect::firstOrCreate(
                    ['from_path' => Redirect::normalizePath($oldPath)],
                    ['to_url' => $newUrl, 'is_active' => true]
                );
                $this->line('  → przekierowanie zarejestrowane.');
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

    /** Zwraca mapę slug → id wszystkich kategorii (tworzy brakujące). */
    private function ensureCategories(): array
    {
        $map = [];
        $order = 10;
        foreach (self::CATEGORY_DEFS as $slug => $name) {
            $cat = NewsCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'order' => $order]
            );
            $map[$slug] = $cat->id;
            $order += 10;
        }
        return $map;
    }

    /** Dopasowuje artykuł do kategorii na podstawie tytułu i treści. */
    private function classifyArticle(string $title, string $content): string
    {
        $haystack = mb_strtolower($title . ' ' . strip_tags($content));

        foreach (self::CATEGORIES_MAP as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($haystack, $kw)) {
                    return $slug;
                }
            }
        }

        return 'ogolne';
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

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($response->body());
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
        $path = ltrim($link, '/');
        $path = preg_replace('#^aktualnosci/#', '', $path);
        $path = preg_replace('#\.html$#', '', $path);
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
        return mb_substr(trim($text), 0, 200);
    }
}
