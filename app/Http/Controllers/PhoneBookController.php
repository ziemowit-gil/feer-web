<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Książka telefoniczna zawsze pod adresem /nasze-numery-telefonow:
 *  1. opublikowana strona o tym adresie (zwykle typu „Kontakt — książka”) — strona ma pierwszeństwo,
 *  2. a gdy takiej nie ma — główna strona książki o innym adresie (typ „Kontakt — książka” bez filtra działu,
 *     potem dawna „Książka telefoniczna”), wyświetlona pod tym adresem,
 *  3. a bez stron — lista z modułu „Książka telefoniczna” (gdy moduł jest włączony).
 * Dawny adres /ksiazka-telefoniczna przekierowuje tu na stałe (301).
 */
class PhoneBookController extends Controller
{
    public const SLUG = 'nasze-numery-telefonow';

    public const OLD_SLUG = 'ksiazka-telefoniczna';

    public function show(Request $request)
    {
        if ($page = self::primaryPage()) {
            return app(PageController::class)->show($request, $page);
        }

        $settings = SiteSetting::current();
        abort_unless($settings->isModuleEnabled('phone_book'), 404);

        return view('phone-book.show', [
            'contacts' => PhoneBookEntry::listForCurrentSite(),
            'settings' => $settings,
        ]);
    }

    /** /ksiazka-telefoniczna → /nasze-numery-telefonow (z zapytaniem). */
    public function legacy(Request $request): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to(url('/'.self::SLUG).($query ? '?'.$query : ''), 301);
    }

    /** Strona pokazywana pod /nasze-numery-telefonow: o tym adresie, a w drugiej kolejności główna strona książki. */
    public static function primaryPage(): ?Page
    {
        $live = fn ($q) => $q->where('is_published', true)->where(fn ($w) => $w->whereNull('publish_at')->orWhere('publish_at', '<=', now()));

        return Page::forCurrentSite()->where($live)->where('slug', self::SLUG)->first()
            ?? Page::forCurrentSite()->where($live)->where('type', 'contact_book')->orderBy('id')->get()
                ->first(fn (Page $p) => blank(($p->type_data ?? [])['group'] ?? null))
            ?? Page::forCurrentSite()->where($live)->where('type', 'phone_book')->orderBy('id')->first();
    }
}
