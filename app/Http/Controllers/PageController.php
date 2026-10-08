<?php

namespace App\Http\Controllers;

use App\Models\BrandAccessUser;
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

        // Strona typu „Kontakt” ma stały układ i własny kontroler.
        if ($page->type === 'contact') {
            return app(ContactController::class)->render();
        }

        // Strona z ograniczonym dostępem (typ wewnętrzny albo poziom dostępu ustawiony na stronie lub jej przodku).
        if ($page->requiresAccess() && ! $page->accessGranted()) {
            if (! $page->isAccessRestricted()) {
                $level = $page->effectiveAccessLevel();
                if ($level === 'member') {
                    session(['url.intended' => url()->current()]);

                    return redirect()->route('member.login');
                }
                if (in_array($level, ['panel', 'groups'], true) && ! auth('web')->check()) {
                    session(['url.intended' => url()->current()]);

                    return redirect()->route('login');
                }
                if ($level === 'password') {
                    return response()->view('page.locked', compact('page'), 403);
                }

                return response()->view('page.locked-role', compact('page'), 403);
            }

            if ($page->isBrandAssets()) {
                return redirect()->route('page.brand-login', $page);
            }

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

        if ($page->isCooperation() && SiteSetting::current()->site_template === 'federation') {
            return response()->view('templates.federation.cooperation', $data);
        }

        $view = match ($page->page_template) {
            'wide'    => 'page.show-wide',
            'hero'    => 'page.show-hero',
            'landing' => 'page.show-landing',
            'portal'  => 'page.show-portal',
            'minimal' => 'page.show-minimal',
            default   => 'page.show',
        };

        return response()->view($view, $data);
    }

    /** Formularz logowania dla strony z zasobami marki (indywidualny login+hasło). */
    public function brandLogin(Request $request, Page $page)
    {
        $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
        abort_unless($isLive && $page->isBrandAssets(), 404);

        if ($page->accessGranted()) {
            return redirect()->route('page.show', $page);
        }

        return response()->view('page.brand-locked', compact('page'));
    }

    /** Weryfikacja loginu i hasła do strony z zasobami marki. */
    public function brandLoginPost(Request $request, Page $page)
    {
        $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
        abort_unless($isLive && $page->isBrandAssets(), 404);

        $data = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = BrandAccessUser::where('page_id', $page->id)
            ->where('login', $data['login'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Nieprawidłowy login lub hasło.'])
                ->withInput(['login' => $data['login']]);
        }

        session(["brand_access_{$page->id}" => $user->id]);
        $user->update(['last_login_at' => now()]);

        return redirect()->route('page.show', $page);
    }

    /** Wylogowanie ze strony z zasobami marki. */
    public function brandLogout(Request $request, Page $page)
    {
        session()->forget("brand_access_{$page->id}");

        return redirect()->route('page.brand-login', $page);
    }

    /** Wyświetla stronę osoby pod adresem /{parentSlug}/osoba/{personSlug}. */
    public function showPerson(Request $request, string $parentSlug, string $personSlug): mixed
    {
        $page = Page::where('slug', "{$parentSlug}/osoba/{$personSlug}")->firstOrFail();

        return $this->show($request, $page);
    }

    /** Odblokowanie strony wewnętrznej hasłem (zapis w sesji). */
    public function unlock(Request $request, Page $page)
    {
        $source = $page->isAccessRestricted() ? ($page->access_mode === 'password' ? $page : null) : $page->accessSource();
        abort_unless($source && ($page->isAccessRestricted() || $source->access_level === 'password'), 404);

        $request->validate(['access_password' => ['required', 'string']]);

        if (! Hash::check($request->input('access_password'), (string) $source->access_password)) {
            return back()->withErrors(['access_password' => 'Nieprawidłowe hasło.']);
        }

        $unlocked = session('unlocked_pages', []);
        $unlocked[] = $source->id;
        session(['unlocked_pages' => array_values(array_unique($unlocked))]);

        return redirect()->route('page.show', $page);
    }
}
