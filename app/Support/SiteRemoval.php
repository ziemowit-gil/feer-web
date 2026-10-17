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
     * Usuwa treść witryny i samą witrynę.
     *
     * @return array<string, int> tabela => usunięte wiersze
     */
    public static function remove(SiteSetting $site): array
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
