<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Models\MediaLibrary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * „Gdzie użyto” — miejsca w serwisie, które odwołują się do pliku z biblioteki.
 *
 * Zamiast utrzymywać listę pól z treścią przeszukujemy wszystkie kolumny tekstowe
 * w bazie (i w bazie bloga) w poszukiwaniu adresu pliku: /storage/{id}/… — także
 * w JSON-ie, gdzie ukośniki bywają poprzedzone „\”. Dzięki temu nowe moduły są
 * objęte bez zmian tutaj. Pomijamy tabele techniczne i historię zmian (rewizje,
 * dziennik), bo stara wersja treści to nie „użycie”.
 *
 * Do tego dochodzi właściciel pliku (np. zdjęcie przypisane do aktualności).
 */
class MediaUsage
{
    /** Tabele, których nie przeszukujemy (dokładne nazwy lub fragmenty nazw). */
    private const SKIP_TABLES = [
        'media', 'media_versions', 'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'password_resets', 'personal_access_tokens', 'notifications',
        'cookie_consents', 'form_submissions', 'search_index',
    ];

    private const SKIP_TABLE_FRAGMENTS = ['revision', 'activity_log', 'telescope', '_log', 'logs', 'pulse_'];

    /** Etykieta i ekran edycji dla znanych tabel; reszta pokazuje się pod nazwą tabeli. */
    private const TABLES = [
        'pages' => ['label' => 'Strona', 'route' => 'admin.podstrony.edit'],
        'news' => ['label' => 'Aktualność', 'route' => 'admin.newsy.edit'],
        'projects' => ['label' => 'Działanie', 'route' => 'admin.projekty.edit'],
        'events' => ['label' => 'Wydarzenie', 'route' => 'admin.wydarzenia.edit'],
        'job_offers' => ['label' => 'Oferta pracy', 'route' => 'admin.praca.edit'],
        'volunteer_ads' => ['label' => 'Oferta wolontariatu', 'route' => 'admin.wolontariat.edit'],
        'landing_pages' => ['label' => 'Strona docelowa', 'route' => 'admin.lp.edit'],
        'educational_materials' => ['label' => 'Materiał edukacyjny', 'route' => 'admin.materialy-edukacyjne.edit'],
        'podcasts' => ['label' => 'Podcast', 'route' => 'admin.podcasty.edit'],
        'annual_reports' => ['label' => 'Sprawozdanie', 'route' => 'admin.sprawozdania.edit'],
        'banners' => ['label' => 'Baner', 'route' => 'admin.banery.edit'],
        'faqs' => ['label' => 'Pytanie FAQ', 'route' => 'admin.faq.edit'],
        'partners' => ['label' => 'Partner', 'route' => 'admin.partnerzy.edit'],
        'hero_slides' => ['label' => 'Slajd hero', 'route' => 'admin.hero.edit'],
        'gallery_images' => ['label' => 'Galeria', 'route' => 'admin.galeria.edit'],
        'nav_items' => ['label' => 'Pozycja menu', 'route' => 'admin.pozycje-menu.edit'],
        'form_definitions' => ['label' => 'Formularz', 'route' => 'admin.formularze.edit'],
        'site_settings' => ['label' => 'Ustawienia strony', 'route' => 'admin.ustawienia.edit', 'no_param' => true],
        'mail_templates' => ['label' => 'Szablon e-maila', 'route' => 'admin.mail-templates.edit'],
        'blog_articles' => ['label' => 'Wpis bloga', 'route' => 'admin.wiem-feer.edit'],
        'bip_documents' => ['label' => 'Dokument BIP', 'route' => 'admin.bip-dokumenty.edit'],
        'page_images' => ['label' => 'Zdjęcie strony', 'route' => 'admin.podstrony.edit', 'param_column' => 'page_id'],
    ];

    private const TITLE_COLUMNS = ['title', 'name', 'site_name', 'subject', 'question', 'label', 'slug'];

    /** Kolumny tekstowe (z nazwą tabeli i połączeniem) — wyliczane raz na proces. */
    private ?array $columns = null;

    /**
     * Miejsca użycia jednego pliku.
     *
     * @return array<int, array{label: string, title: string, url: ?string, source: string}>
     */
    public function forMedia(Media $media): array
    {
        $hits = [];

        // Właściciel pliku (zdjęcie przypisane do treści), poza samą biblioteką.
        if ($media->model_type !== MediaLibrary::class && class_exists($media->model_type)) {
            $owner = $media->model_type::find($media->model_id);
            $table = $owner?->getTable();
            $hits[] = [
                'label' => (self::TABLES[$table]['label'] ?? class_basename($media->model_type)) . ' (plik przypisany)',
                'title' => $owner ? $this->ownerTitle($owner) : 'usunięty rekord',
                'url' => $owner ? $this->editUrl($table, $owner->getAttribute(self::TABLES[$table]['param_column'] ?? $owner->getKeyName())) : null,
                'source' => $table ?? '',
            ];
        }

        $id = (int) $media->id;
        foreach ($this->textColumns() as [$connection, $table, $cols, $key]) {
            foreach ($cols as $col) {
                try {
                    $rows = DB::connection($connection)->table($table)
                        ->where($col, 'like', '%storage%')
                        ->where($col, 'like', '%/' . $id . '%')
                        ->limit(200)
                        ->get();
                } catch (Throwable) {
                    continue;
                }
                foreach ($rows as $row) {
                    $row = (array) $row;
                    if (! in_array($id, $this->idsIn((string) $row[$col]), true)) {
                        continue;
                    }
                    $rowKey = $row[$key] ?? null;
                    $hitKey = $connection . '.' . $table . '.' . $rowKey;
                    if (isset($hits[$hitKey])) {
                        continue; // ten sam rekord, inna kolumna
                    }
                    $hits[$hitKey] = [
                        'label' => self::TABLES[$table]['label'] ?? 'Tabela ' . $table,
                        'title' => $this->titleOf($row, $rowKey),
                        'url' => $rowKey !== null ? $this->editUrl($table, $rowKey) : null,
                        'source' => $table,
                    ];
                }
            }
        }

        return array_values($hits);
    }

    /**
     * Liczba miejsc użycia dla każdego pliku, do którego cokolwiek się odwołuje
     * (jedno przejście po bazie; wynik w pamięci podręcznej na kilka minut).
     *
     * @return array<int, int> id pliku => liczba rekordów z odwołaniem
     */
    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('media-usage-counts');
        }

        return Cache::remember('media-usage-counts', now()->addMinutes(10), function () {
            $counts = [];
            foreach ($this->textColumns() as [$connection, $table, $cols, $key]) {
                $perRow = [];
                foreach ($cols as $col) {
                    try {
                        $rows = DB::connection($connection)->table($table)
                            ->where($col, 'like', '%storage%')
                            ->select([$key, $col])
                            ->lazyById(500, $key);
                        foreach ($rows as $row) {
                            $row = (array) $row;
                            foreach ($this->idsIn((string) $row[$col]) as $mid) {
                                $perRow[$mid][$row[$key]] = true;
                            }
                        }
                    } catch (Throwable) {
                        // tabela bez klucza do stronicowania albo nietypowa kolumna — pomijamy
                    }
                }
                foreach ($perRow as $mid => $rowsWithRef) {
                    $counts[$mid] = ($counts[$mid] ?? 0) + count($rowsWithRef);
                }
            }

            // Pliki przypisane do treści (zdjęcie aktualności, slajdu…) też są używane.
            Media::query()->where('model_type', '!=', MediaLibrary::class)->pluck('id')
                ->each(function ($mid) use (&$counts) {
                    $counts[$mid] = ($counts[$mid] ?? 0) + 1;
                });

            return $counts;
        });
    }

    public function forget(): void
    {
        Cache::forget('media-usage-counts');
    }

    /** Id plików wskazanych w tekście: /storage/{id}/ oraz \/storage\/{id}\/ (JSON). */
    private function idsIn(string $text): array
    {
        if (! preg_match_all('#storage\\\\?/(\d+)\\\\?/#', $text, $m)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $m[1])));
    }

    /**
     * Kolumny tekstowe do przeszukania: [połączenie, tabela, kolumny, kolumna klucza].
     *
     * @return array<int, array{0: string, 1: string, 2: array<string>, 3: string}>
     */
    private function textColumns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $out = [];
        foreach ($this->connections() as $connection) {
            try {
                $schema = Schema::connection($connection);
                $tables = collect($schema->getTables())->pluck('name');
            } catch (Throwable) {
                continue;
            }
            foreach ($tables as $table) {
                if ($this->skipped($table)) {
                    continue;
                }
                try {
                    $columns = collect($schema->getColumns($table));
                } catch (Throwable) {
                    continue;
                }
                $key = $columns->firstWhere('name', 'id') ? 'id' : null;
                if (! $key) {
                    continue;
                }
                $cols = $columns->filter(function ($c) {
                    $type = strtolower((string) ($c['type_name'] ?? $c['type'] ?? ''));

                    return str_contains($type, 'text') || str_contains($type, 'char') || str_contains($type, 'json') || $type === 'clob';
                })->pluck('name')->reject(fn ($n) => in_array($n, ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'], true))->values()->all();

                if ($cols) {
                    $out[] = [$connection, $table, $cols, $key];
                }
            }
        }

        return $this->columns = $out;
    }

    private function connections(): array
    {
        $list = [config('database.default')];
        if (config('database.connections.blog')) {
            $list[] = 'blog';
        }

        return array_values(array_unique($list));
    }

    private function skipped(string $table): bool
    {
        if (in_array($table, self::SKIP_TABLES, true) || str_starts_with($table, 'sqlite_')) {
            return true;
        }
        foreach (self::SKIP_TABLE_FRAGMENTS as $fragment) {
            if (str_contains($table, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /** Tytuł właściciela pliku — dla zdjęć stron tytuł strony. */
    private function ownerTitle($owner): string
    {
        if ($owner->getTable() === 'page_images' && $owner->page_id) {
            $page = DB::table('pages')->where('id', $owner->page_id)->first();
            if ($page) {
                return $this->titleOf((array) $page, $page->id);
            }
        }

        return $this->titleOf((array) $owner->getAttributes(), $owner->getKey());
    }

    private function titleOf(array $row, mixed $key): string
    {
        foreach (self::TITLE_COLUMNS as $col) {
            if (filled($row[$col] ?? null) && is_string($row[$col])) {
                return mb_strimwidth(strip_tags($row[$col]), 0, 90, '…');
            }
        }

        return '#' . $key;
    }

    private function editUrl(?string $table, mixed $key): ?string
    {
        $map = self::TABLES[$table] ?? null;
        if (! $map || ! Route::has($map['route'])) {
            return null;
        }

        try {
            return ! empty($map['no_param']) ? route($map['route']) : route($map['route'], $key);
        } catch (Throwable) {
            return null;
        }
    }
}
