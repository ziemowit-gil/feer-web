<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->is_published, 404);

        // Strona wewnętrzna (także „Panel współpracownika"): sprawdź autoryzację.
        if ($page->isAccessRestricted() && ! $page->accessGranted()) {
            if ($page->access_mode === 'microsoft') {
                // Osobne logowanie do strefy wewnętrznej (MS365, guard „member").
                session(['url.intended' => url()->current()]);

                return redirect()->route('member.login');
            }

            return response()->view('page.locked', compact('page'), 403);
        }

        return response()->view('page.show', compact('page'));
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
