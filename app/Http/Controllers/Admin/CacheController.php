<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Panel admin: zarządzanie cache Redis — status, konfiguracja TTL, czyszczenie.
 *
 * Metody: index(), update(), flush(), flushAll().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CacheController extends Controller
{
    /** Grupy cache zarządzane przez ten panel. */
    private const GROUPS = [
        'news' => [
            'label'    => 'Aktualności',
            'icon'     => 'fa-newspaper',
            'named'    => [
                ['key' => 'news_categories', 'label' => 'Lista kategorii',            'ttl_key' => 'news_categories'],
                ['key' => 'news_latest3',    'label' => '3 ostatnie (strona główna)', 'ttl_key' => 'news_item'],
            ],
            'prefix'   => 'news_item_',
            'ttl_key'  => 'news_item',
            'default_ttl' => 3600,
        ],
        'events' => [
            'label'    => 'Grafik / Wydarzenia',
            'icon'     => 'fa-calendar-days',
            'named'    => [
                ['key' => 'events_upcoming', 'label' => 'Lista nadchodzących', 'ttl_key' => 'events_upcoming'],
            ],
            'prefix'   => 'event_item_',
            'ttl_key'  => 'event_item',
            'default_ttl' => 3600,
        ],
        'pages' => [
            'label'    => 'Strony',
            'icon'     => 'fa-file-lines',
            'named'    => [
                ['key' => 'page_about_motto', 'label' => 'Motto — slajder hero',      'ttl_key' => 'page_item'],
                ['key' => 'page_about_first', 'label' => 'Strona „O organizacji"',    'ttl_key' => 'page_item'],
            ],
            'prefix'   => 'page_item_',
            'ttl_key'  => 'page_item',
            'default_ttl' => 3600,
        ],
    ];

    /** Wyświetla panel cache: status kluczy, konfigurację TTL, przyciski czyszczenia. */
    public function index()
    {
        $settings = SiteSetting::current();
        $driver = config('cache.default', 'database');

        $groups = [];
        foreach (self::GROUPS as $id => $def) {
            $namedKeys = array_map(fn ($k) => array_merge($k, [
                'cached' => Cache::has($k['key']),
                'ttl'    => $settings->cacheTtl($k['ttl_key'], SiteSetting::CACHE_DEFAULTS[$k['ttl_key'] . '_ttl'] ?? 3600),
            ]), $def['named']);

            $groups[$id] = array_merge($def, [
                'enabled'      => $settings->cacheEnabled($id),
                'item_ttl'     => $settings->cacheTtl($def['ttl_key'], $def['default_ttl']),
                'item_count'   => $this->countByPrefix($def['prefix'], $driver),
                'named'        => $namedKeys,
            ]);
        }

        return view('admin.cache.index', [
            'groups'   => $groups,
            'driver'   => $driver,
            'settings' => $settings,
        ]);
    }

    /** Zapisuje konfigurację cache (enable/disable, TTL). */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'news_enabled'        => ['sometimes', 'boolean'],
            'events_enabled'      => ['sometimes', 'boolean'],
            'pages_enabled'       => ['sometimes', 'boolean'],
            'news_item_ttl'       => ['required', 'integer', 'min:60', 'max:86400'],
            'news_categories_ttl' => ['required', 'integer', 'min:60', 'max:604800'],
            'event_item_ttl'      => ['required', 'integer', 'min:60', 'max:86400'],
            'events_upcoming_ttl' => ['required', 'integer', 'min:60', 'max:86400'],
            'page_item_ttl'       => ['required', 'integer', 'min:60', 'max:86400'],
        ]);

        $config = [
            'news_enabled'        => $request->boolean('news_enabled'),
            'events_enabled'      => $request->boolean('events_enabled'),
            'pages_enabled'       => $request->boolean('pages_enabled'),
            'news_item_ttl'       => (int) $data['news_item_ttl'],
            'news_categories_ttl' => (int) $data['news_categories_ttl'],
            'event_item_ttl'      => (int) $data['event_item_ttl'],
            'events_upcoming_ttl' => (int) $data['events_upcoming_ttl'],
            'page_item_ttl'       => (int) $data['page_item_ttl'],
        ];

        SiteSetting::current()->update(['cache_config' => $config]);

        // Po zapisie zmiany wchodzą w życie od razu — bieżące klucze będą
        // respektowane przy następnym wygaśnięciu; wymuś odświeżenie tylko
        // dla grup, które właśnie wyłączono.
        foreach (['news', 'events', 'pages'] as $group) {
            if (! $config["{$group}_enabled"]) {
                $this->flushGroup($group);
            }
        }

        return redirect()->route('admin.cache.index')
            ->with('status', 'Konfiguracja cache została zapisana.');
    }

    /** Czyści cache wybranej grupy. */
    public function flush(string $group): RedirectResponse
    {
        abort_unless(array_key_exists($group, self::GROUPS), 404);

        $this->flushGroup($group);

        $label = self::GROUPS[$group]['label'];

        return redirect()->route('admin.cache.index')
            ->with('status', "Wyczyszczono cache: {$label}.");
    }

    /** Czyści cały cache aplikacji (artisan cache:clear). */
    public function flushAll(): RedirectResponse
    {
        Artisan::call('cache:clear');

        return redirect()->route('admin.cache.index')
            ->with('status', 'Wyczyszczono cały cache aplikacji.');
    }

    // -------------------------------------------------------------------------
    // Pomocniki prywatne
    // -------------------------------------------------------------------------

    private function flushGroup(string $group): void
    {
        $def = self::GROUPS[$group];

        foreach ($def['named'] as $namedKey) {
            Cache::forget($namedKey['key']);
        }

        $this->deleteByPrefix($def['prefix']);
    }

    /**
     * Zlicza klucze cache z danym prefiksem.
     * Zwraca null, gdy sterownik nie obsługuje skanowania (nie jest redis/database).
     */
    private function countByPrefix(string $prefix, string $driver): ?int
    {
        try {
            if ($driver === 'redis') {
                $cachePrefix = config('cache.prefix') . ':';
                return count(Redis::connection('cache')->keys("{$cachePrefix}{$prefix}*"));
            }

            if ($driver === 'database') {
                $cachePrefix = config('cache.prefix');
                return DB::table('cache')
                    ->where('key', 'like', "{$cachePrefix}{$prefix}%")
                    ->count();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /** Czytelna etykieta TTL (np. „1 h 30 min") — używana w szablonie Blade. */
    public static function formatTtlStatic(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} s";
        }
        if ($seconds < 3600) {
            return round($seconds / 60) . ' min';
        }
        if ($seconds < 86400) {
            $h = intdiv($seconds, 3600);
            $m = round(($seconds % 3600) / 60);
            return $m > 0 ? "{$h} h {$m} min" : "{$h} h";
        }
        $d = intdiv($seconds, 86400);
        $h = round(($seconds % 86400) / 3600);
        return $h > 0 ? "{$d} d {$h} h" : "{$d} d";
    }

    /**
     * Usuwa klucze cache z danym prefiksem.
     * Dla Redis: SCAN + DEL; dla bazy: DELETE LIKE.
     */
    private function deleteByPrefix(string $prefix): void
    {
        $driver = config('cache.default', 'database');

        try {
            if ($driver === 'redis') {
                $cachePrefix = config('cache.prefix') . ':';
                $keys = Redis::connection('cache')->keys("{$cachePrefix}{$prefix}*");
                if ($keys) {
                    Redis::connection('cache')->del($keys);
                }
                return;
            }

            if ($driver === 'database') {
                $cachePrefix = config('cache.prefix');
                DB::table('cache')
                    ->where('key', 'like', "{$cachePrefix}{$prefix}%")
                    ->delete();
            }
        } catch (\Throwable) {
        }
    }
}
