<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorizationRequest;
use App\Models\Authorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel admin: zarządzanie rejestrem pełnomocnictw i upoważnień
 * (lista z wyszukiwarką, dodawanie, edycja, unieważnianie, usuwanie).
 * Publiczna strona rejestru prezentuje te wpisy pod /rejestr-pelnomocnictw.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), toggleActive().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AuthorizationController extends Controller
{
    /** Lista wpisów rejestru z wyszukiwarką i filtrem rodzaju. */
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q'));
        $type = (string) $request->string('typ');

        $query = Authorization::query()->orderByDesc('valid_from')->orderBy('grantee_name');

        if ($q !== '') {
            $query->search($q);
        }
        if (array_key_exists($type, Authorization::TYPES)) {
            $query->where('type', $type);
        } else {
            $type = '';
        }

        return view('admin.authorizations.index', [
            'authorizations' => $query->paginate(20)->withQueryString(),
            'q'              => $q,
            'type'           => $type,
            'types'          => Authorization::TYPES,
        ]);
    }

    /** Formularz nowego wpisu. */
    public function create(): View
    {
        return view('admin.authorizations.form', ['authorization' => new Authorization()]);
    }

    /** Zapisuje nowy wpis rejestru. */
    public function store(AuthorizationRequest $request): RedirectResponse
    {
        Authorization::create($request->validated());

        return redirect()->route('admin.pelnomocnictwa.index')
            ->with('status', 'Wpis został dodany do rejestru.');
    }

    /** Formularz edycji wpisu. */
    public function edit(Authorization $pelnomocnictwo): View
    {
        return view('admin.authorizations.form', ['authorization' => $pelnomocnictwo]);
    }

    /** Aktualizuje wpis rejestru. */
    public function update(AuthorizationRequest $request, Authorization $pelnomocnictwo): RedirectResponse
    {
        $pelnomocnictwo->update($request->validated());

        return redirect()->route('admin.pelnomocnictwa.index')
            ->with('status', 'Wpis został zaktualizowany.');
    }

    /** Usuwa wpis z rejestru (do pomyłek; unieważnienie = toggleActive). */
    public function destroy(Authorization $pelnomocnictwo): RedirectResponse
    {
        $pelnomocnictwo->delete();

        return redirect()->route('admin.pelnomocnictwa.index')
            ->with('status', 'Wpis został usunięty z rejestru.');
    }

    /** Szybkie unieważnienie / przywrócenie wpisu z listy. */
    public function toggleActive(Authorization $pelnomocnictwo): RedirectResponse
    {
        $pelnomocnictwo->update(['is_active' => ! $pelnomocnictwo->is_active]);

        return redirect()->back()->with('status', $pelnomocnictwo->is_active
            ? 'Wpis został przywrócony.'
            : 'Wpis został unieważniony.');
    }
}
