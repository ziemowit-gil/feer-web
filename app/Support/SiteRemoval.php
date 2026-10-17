<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wymuszone usunięcie sub-witryny razem z jej treścią (System → Witryny sieci → Usuń).
 * Treść = wiersze z site_id witryny we wszystkich tabelach. Rekordy, które mają model Eloquent,
 * usuwamy przez model (forceDelete przy SoftDeletes) — znikają też ich pliki multimediów, wersje
 * i powiązania obsługiwane w zdarzeniach modeli; pozostałe tabele czyścimy zapytaniem.
 */
class SiteRemoval
{
    /** Tabele z kolumną site_id (bez site_settings). */
    public static function tables(): array
    {
        return collect(Schema::getTables())->pluck('name')
            ->reject(fn ($t) => $t === 'site_settings' || str_starts_with($t, 'sqlite_'))
            ->filter(fn ($t) => Schema::hasColumn($t, 'site_id'))
            ->sort()->values()->all();
    }

    /** @return array<string, int> tabela => liczba wierszy witryny (tylko niezerowe) */
    public static function contentCounts(SiteSetting $site): array
    {
        $out = [];
        foreach (self::tables() as $t) {
            $n = DB::table($t)->where('site_id', $site->id)->count();
            if ($n > 0) {
                $out[$t] = $n;
            }
        }

        return $out;
    }

    /**
     * Witryna i wszystkie jej podwitryny (rekurencyjnie), od najgłębszych — w tej kolejności je usuwamy.
     *
     * @return list<SiteSetting>
     */
    public static function withDescendants(SiteSetting $site): array
    {
        $out = [];
        foreach ($site->subsites()->get() as $child) {
            array_push($out, ...self::withDescendants($child));
        }
        $out[] = $site;

        return $out;
    }

    /**
     * Przenosi witrynę (z podwitrynami) do kosza na SiteSetting::TRASH_DAYS dni — treść zostaje nietknięta,
     * witryna znika z publicznego routingu i listy witryn.
     *
     * @return int liczba witryn przeniesionych do kosza
     */
    public static function trash(SiteSetting $site): int
    {
        $sites = self::withDescendants($site);
        $now = now();
        DB::transaction(function () use ($sites, $now) {
            foreach ($sites as $s) {
                $s->forceFill(['trashed_at' => $now])->saveQuietly();
            }
        });

        return count($sites);
    }

    /** Przywraca witrynę z kosza razem z podwitrynami, które trafiły tam razem z nią. @return int */
    public static function restore(SiteSetting $site): int
    {
        $stamp = $site->trashed_at;
        $sites = array_filter(self::withDescendants($site), fn ($s) => $s->id === $site->id || (string) $s->trashed_at === (string) $stamp);
        DB::transaction(function () use ($sites) {
            foreach ($sites as $s) {
                $s->forceFill(['trashed_at' => null])->saveQuietly();
            }
        });

        return count($sites);
    }

    /**
     * Witryny w koszu do pokazania na liście: tylko „korzenie” (podwitryna usunięta razem z rodzicem jest w nim zawarta).
     *
     * @return \Illuminate\Support\Collection<int, SiteSetting>
     */
    public static function trashed(): \Illuminate\Support\Collection
    {
        $all = SiteSetting::query()->onlyTrashed()->orderBy('trashed_at')->get();
        $ids = $all->pluck('id')->all();

        return $all->reject(fn ($s) => in_array($s->parent_site_id, $ids, true))->values();
    }

    /**
     * Trwale usuwa witryny, które leżą w koszu dłużej niż SiteSetting::TRASH_DAYS dni.
     *
     * @return list<string> nazwy usuniętych witryn
     */
    public static function purgeExpired(): array
    {
        $done = [];
        foreach (self::trashed() as $site) {
            if ($site->purgeAt() && $site->purgeAt()->isPast()) {
                $name = $site->site_name;
                self::remove($site, true);
                $done[] = $name;
            }
        }

        return $done;
    }

    /** @return array<string, int> suma rekordów witryny i jej podwitryn */
    public static function contentCountsDeep(SiteSetting $site): array
    {
        $out = [];
        foreach (self::withDescendants($site) as $s) {
            foreach (self::contentCounts($s) as $t => $n) {
                $out[$t] = ($out[$t] ?? 0) + $n;
            }
        }
        ksort($out);

        return $out;
    }

    /**
     * Usuwa treść witryny i samą witrynę; z $withSubsites — najpierw wszystkie podwitryny (z treścią).
     *
     * @return array<string, int> tabela => usunięte wiersze
     */
    public static function remove(SiteSetting $site, bool $withSubsites = false): array
    {
        $removed = [];
        DB::transaction(function () use ($site, $withSubsites, &$removed) {
            foreach ($withSubsites ? self::withDescendants($site) : [$site] as $s) {
                foreach (self::removeOne($s) as $t => $n) {
                    $removed[$t] = ($removed[$t] ?? 0) + $n;
                }
            }
        });

        return $removed;
    }

    /** @return array<string, int> */
    private static function removeOne(SiteSetting $site): array
    {
        $models = self::modelsByTable();
        $removed = [];

        DB::transaction(function () use ($site, $models, &$removed) {
            foreach (self::contentCounts($site) as $table => $count) {
                if (isset($models[$table])) {
                    $class = $models[$table];
                    $query = $class::query()->withoutGlobalScopes()->where('site_id', $site->id);
                    if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                        $query->withTrashed();
                    }
                    $query->get()->each(fn (Model $m) => method_exists($m, 'forceDelete') ? $m->forceDelete() : $m->delete());
                }
                // Co zostało (tabele bez modelu albo wiersze pominięte przez zdarzenia) — zapytaniem.
                DB::table($table)->where('site_id', $site->id)->delete();
                $removed[$table] = $count;
            }

            $site->delete();
            $removed['site_settings'] = ($removed['site_settings'] ?? 0) + 1;
        });

        return $removed;
    }

    /** @return array<string, class-string<Model>> tabela => klasa modelu (app/Models) */
    private static function modelsByTable(): array
    {
        $map = [];
        foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            if (! class_exists($class) || ! is_subclass_of($class, Model::class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }
            $table = (new $class)->getTable();
            $map[$table] ??= $class;
        }

        return $map;
    }
}
