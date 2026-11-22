<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Zmiana witryny głównej instalacji (SiteSetting::main()): wskazana witryna traci rodzica, a dotychczasowa główna
 * (i każda inna witryna bez rodzica) staje się jej subwitryną. Treść, ustawienia, podwitryny, adres skrócony i domena
 * nie zmieniają się, więc dawne linki do nowej głównej działają dalej, a zmianę cofa się tym samym przełączeniem.
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
}
