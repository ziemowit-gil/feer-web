<?php

namespace App\Support;

use App\Http\Middleware\ResolveCurrentSite;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Zmiana witryny głównej instalacji (SiteSetting::main()): wskazana witryna traci rodzica, a dotychczasowa główna
 * (i każda inna witryna bez rodzica) staje się jej subwitryną. Treść, ustawienia, podwitryny, adres skrócony i domena
 * nie zmieniają się, więc dawne linki do nowej głównej działają dalej, a zmianę cofa się tym samym przełączeniem.
 * Opcjonalnie usuwa wszystkie pozostałe witryny (removeOthers) — główna zostaje zawsze.
 */
class MainSite
{
    /** Powód, dla którego witryna nie może zostać główną, albo null, gdy może. */
    public static function refusal(SiteSetting $site): ?string
    {
        if (SiteSetting::hasTrashColumn() && $site->isTrashed()) {
            return 'Witryna „'.$site->site_name.'” jest w koszu. Przywróć ją, zanim ustawisz ją jako główną.';
        }

        if ($site->isBipSite()) {
            return 'Witryna „'.$site->site_name.'” to Biuletyn Informacji Publicznej — BIP działa jako subwitryna organizacji, nie jako witryna główna.';
        }

        if (SiteSetting::main()->is($site)) {
            return 'Witryna „'.$site->site_name.'” już jest witryną główną.';
        }

        return null;
    }

    /**
     * Ustawia witrynę jako główną. Zwraca dotychczasową główną.
     *
     * @throws \InvalidArgumentException gdy witryna nie może zostać główną (patrz refusal())
     */
    public static function switchTo(SiteSetting $site, ?User $by = null): SiteSetting
    {
        if ($reason = self::refusal($site)) {
            throw new \InvalidArgumentException($reason);
        }

        $previous = SiteSetting::main();

        DB::transaction(function () use ($site) {
            // Przez model, nie zapytanie: hooki saved czyszczą SiteSetting::$cached i mapę adresów skróconych.
            SiteSetting::query()->whereNull('parent_site_id')->whereKeyNot($site->getKey())->get()
                ->each(fn (SiteSetting $root) => $root->update(['parent_site_id' => $site->getKey()]));

            $site->update(['parent_site_id' => null]);
        });

        if (function_exists('activity')) {
            activity('system')->causedBy($by)->event('updated')
                ->withProperties(['before' => $previous->site_name, 'after' => $site->site_name, 'site_id' => $site->getKey()])
                ->log('Zmieniono witrynę główną z „'.$previous->site_name.'” na „'.$site->site_name.'”');
        }

        return $previous;
    }

    /**
     * Witryny do usunięcia przy „Usuń pozostałe witryny”: najwyższe w drzewie poza główną — każda razem ze swoimi
     * podwitrynami (SiteRemoval::withDescendants). Bez $withTrashed tylko witryny poza koszem.
     *
     * @return Collection<int, SiteSetting>
     */
    public static function others(bool $withTrashed = false): Collection
    {
        $main = SiteSetting::main();
        $all = SiteSetting::query()->whereKeyNot($main->getKey())
            ->when(! $withTrashed, fn ($q) => $q->notTrashed())
            ->orderBy('id')->get();
        $ids = $all->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $all->reject(fn (SiteSetting $s) => $s->parent_site_id !== null && in_array((int) $s->parent_site_id, $ids, true))->values();
    }

    /**
     * Usuwa wszystkie witryny poza główną: do kosza (SiteSetting::TRASH_DAYS dni na przywrócenie) albo trwale razem
     * z treścią — wtedy także te, które już leżą w koszu. Witryna główna nigdy nie jest usuwana.
     *
     * @return array{sites: int, removed: array<string, int>} liczba usuniętych witryn i (przy trwałym) usunięte rekordy
     */
    public static function removeOthers(bool $permanent): array
    {
        $main = SiteSetting::main();
        $tops = self::others($permanent);

        foreach ($tops as $top) {
            foreach (SiteRemoval::withDescendants($top) as $site) {
                if ($site->is($main)) {
                    throw new \LogicException('Witryna główna nie może zostać usunięta.');
                }
            }
        }

        $sites = 0;
        $removed = [];
        foreach ($tops as $top) {
            if ($permanent) {
                foreach (SiteRemoval::remove($top, true) as $table => $n) {
                    $removed[$table] = ($removed[$table] ?? 0) + $n;
                }
            } else {
                $sites += SiteRemoval::trash($top);
            }
        }

        if ($permanent) {
            $sites = $removed['site_settings'] ?? 0;
            unset($removed['site_settings']);
        }

        // Kosz zapisuje witryny bez zdarzeń modelu — mapę adresów skróconych czyścimy sami.
        Cache::forget(ResolveCurrentSite::SLUG_MAP_CACHE_KEY);

        return ['sites' => $sites, 'removed' => $removed];
    }
}
