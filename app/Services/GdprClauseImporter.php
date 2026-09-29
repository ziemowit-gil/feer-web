<?php

namespace App\Services;

use App\Models\GdprClause;
use App\Support\SafeHtml;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Import klauzul RODO z SZO do lokalnej tabeli `gdpr_clauses`.
 *
 * - dopasowanie po (slug, lang); treść, tytuł, wersja i adresy nadpisywane,
 * - `is_visible` i `sort_order` (ustawiane w panelu) zostają nietknięte,
 * - klauzula, której SZO już nie publikuje, znika z lokalnej listy
 *   (wycofanej klauzuli nie wolno dalej pokazywać jako obowiązującej),
 * - HTML przechodzi przez SafeHtml::clean() przed zapisem.
 *
 * Wywołania: polecenie `szo:import-clauses` (harmonogram, codziennie)
 * i przycisk „Importuj teraz” w panelu (Admin → Klauzule RODO).
 */
class GdprClauseImporter
{
    public const LAST_RUN_KEY = 'gdpr_clauses.last_import';

    public function __construct(private SzoClient $szo) {}

    /** @return array{added:int, updated:int, unchanged:int, removed:int, total:int} */
    public function import(): array
    {
        $remote = $this->szo->gdprClauses();
        // Pusta odpowiedź przy niepustej kopii lokalnej to najpewniej błąd po stronie
        // SZO (np. zła baza po wdrożeniu) — nie czyścimy strony /rodo na tej podstawie.
        if ($remote === [] && GdprClause::exists()) {
            throw new \RuntimeException('SZO zwróciło pustą listę klauzul — lokalna kopia została bez zmian. Jeśli klauzule faktycznie wycofano, ukryj je w panelu.');
        }
        $now = now();
        $stats = ['added' => 0, 'updated' => 0, 'unchanged' => 0, 'removed' => 0, 'total' => 0];

        DB::transaction(function () use ($remote, $now, &$stats) {
            $seen = [];
            $nextSort = (int) GdprClause::max('sort_order') + 1;

            foreach ($remote as $item) {
                $slug = strtolower(trim((string) ($item['slug'] ?? '')));
                $lang = strtolower(trim((string) ($item['lang'] ?? 'pl')));
                $title = trim((string) ($item['title'] ?? ''));
                if (! preg_match('/^[a-z0-9-]{1,64}$/', $slug) || ! preg_match('/^[a-z]{2}$/', $lang) || $title === '') {
                    continue;
                }
                $seen[] = $slug . '|' . $lang;

                $data = [
                    'title' => mb_substr($title, 0, 255),
                    'html' => SafeHtml::clean((string) ($item['html'] ?? '')),
                    'version' => (int) ($item['version'] ?? 0),
                    'remote_updated_at' => ! empty($item['updated_at']) ? Carbon::parse($item['updated_at']) : null,
                    'url' => $this->safeUrl($item['url'] ?? null),
                    'pdf_url' => $this->safeUrl($item['pdf_url'] ?? null),
                    'imported_at' => $now,
                ];

                $clause = GdprClause::where('slug', $slug)->where('lang', $lang)->first();
                if (! $clause) {
                    GdprClause::create($data + ['slug' => $slug, 'lang' => $lang, 'is_visible' => true, 'sort_order' => $nextSort++]);
                    $stats['added']++;

                    continue;
                }

                $clause->fill($data);
                $changed = $clause->isDirty(['title', 'html', 'version', 'remote_updated_at', 'url', 'pdf_url']);
                $clause->save();
                $stats[$changed ? 'updated' : 'unchanged']++;
            }

            foreach (GdprClause::all() as $local) {
                if (! in_array($local->slug . '|' . $local->lang, $seen, true)) {
                    $local->delete();
                    $stats['removed']++;
                }
            }
        });

        $stats['total'] = GdprClause::count();
        Cache::forever(self::LAST_RUN_KEY, ['at' => $now->toIso8601String(), 'stats' => $stats]);

        return $stats;
    }

    /** Ostatni udany import: ['at' => ISO 8601, 'stats' => [...]] albo null. */
    public static function lastRun(): ?array
    {
        return Cache::get(self::LAST_RUN_KEY);
    }

    private function safeUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        return preg_match('#^https?://#i', $url) ? mb_substr($url, 0, 500) : null;
    }
}
