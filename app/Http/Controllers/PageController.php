<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\SzoKomunikaty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Wyświetlanie podstron z kontrolą dostępu (hasło / MS365), obsługą podglądu szkicu
 * oraz formularzem odblokowywania chronionych treści.
 *
 * Metody: show(), unlock().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PageController extends Controller
{
    /** Slug automatycznie zakładanej strony „Strefa współpracownika". */
    private const STREFA_SLUG = 'strefa-wspolpracownika-feer';

    /** Wyświetla podstronę z kontrolą dostępu (hasło / MS365) i obsługą podglądu szkicu. */
    public function show(Request $request, Page $page)
    {
        $preview = $this->isPreviewRequest($request);
        $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
        abort_unless($isLive || $preview, 404);

        // Strona wewnętrzna (także „Panel współpracownika"): sprawdź autoryzację.
        if ($page->isAccessRestricted() && ! $page->accessGranted()) {
            if ($page->access_mode === 'microsoft') {
                // Osobne logowanie do strefy wewnętrznej (MS365, guard „member").
                session(['url.intended' => url()->current()]);

                return redirect()->route('member.login');
            }

            return response()->view('page.locked', compact('page'), 403);
        }

        $data = ['page' => $page, 'preview' => $preview];

        // W strefie współpracownika dokładamy komunikaty z systemu SZO. Tu jest już
        // po kontroli dostępu, więc trafią tylko do zalogowanego współpracownika.
        if ($page->slug === self::STREFA_SLUG) {
            $settings = SiteSetting::current();
            $szoUrl = $settings->szoKomunikatyUrl();
            $data['szoKomunikaty'] = $szoUrl
                ? SzoKomunikaty::fetch($szoUrl)
                : ['ok' => false, 'items' => []];
            // Adres pełnego Panelu Współpracownika (SZO) do komunikatu na stronie.
            $data['szoPanelUrl'] = $settings->szoPanelUrl();
        }

        return response()->view('page.show', $data);
    }

    /** Odblokowanie strony wewnętrznej hasłem (zapis w sesji). */
    public function unlock(Request $request, Page $page)
    {
        abort_unless($page->isAccessRestricted() && $page->access_mode === 'password', 404);

        $request->validate(['access_password' => ['required', 'string']]);

        if (! Hash::check($request->input('access_password'), (string) $page->access_password)) {
            return back()->withErrors(['access_password' => 'Nieprawidłowe hasło.']);
        }

        $unlocked = session('unlocked_pages', []);
        $unlocked[] = $page->id;
        session(['unlocked_pages' => array_values(array_unique($unlocked))]);

        return redirect()->route('page.show', $page);
    }
}
