<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Panel admin: edycja kodu HTML osadzającego zewnętrzny formularz zapisu do newslettera.
 *
 * Metody: edit(), update().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NewsletterController extends Controller
{
    /** Wyświetla formularz edycji kodu osadzającego newsletter. */
    public function edit()
    {
        return view('admin.newsletter.edit', ['settings' => SiteSetting::current()]);
    }

    /** Zapisuje nowy kod HTML formularza newslettera. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'newsletter_code' => ['nullable', 'string'],
        ]);

        SiteSetting::current()->update($data);

        return redirect()->route('admin.newsletter.edit')->with('status', 'Kod formularza newslettera został zapisany.');
    }
}
