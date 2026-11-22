<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\KreatywniMenuSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Menu nawigacji → „Odwzoruj w panelu 1:1” (szablon „kreatywni”, patrz App\Support\KreatywniMenuSync). */
class KreatywniMenuController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni', 404);

        $created = KreatywniMenuSync::materialize($site);

        if ($created > 0 && function_exists('activity')) {
            activity('system')->causedBy($request->user())->event('created')
                ->withProperties(['site' => $site->site_name, 'items' => $created])
                ->log('Odwzorowano menu strony w panelu (szablon Kreatywni)');
        }

        return redirect()->route('admin.pozycje-menu.index', ['location' => 'main'])->with('status', $created > 0
            ? 'Odwzorowano menu strony w panelu: dodano '.$created.' '.trans_choice('podpozycję|podpozycje|podpozycji', $created).'. Teraz to, co widać w menu na stronie, jest tutaj i można to edytować.'
            : 'Menu w panelu już odpowiada menu na stronie — nie było czego dodać.');
    }
}
