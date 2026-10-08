<?php

namespace App\Http\Controllers;

use App\Models\Authorization;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Publiczny rejestr pełnomocnictw i upoważnień — przeglądanie i weryfikacja.
 * Rekordy zarządzane są w zewnętrznym systemie; tutaj tylko odczyt
 * z wyszukiwarką (nazwisko / numer dokumentu / zakres) i paginacją.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AuthorizationRegistryController extends Controller
{
    /** Wyświetla rejestr z filtrami przekazywanymi przez query string (GET). */
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q'));
        $type = (string) $request->string('typ');

        $query = Authorization::query()
            ->orderByDesc('valid_from')
            ->orderBy('grantee_name');

        if ($q !== '') {
            $query->search($q);
        }

        // Filtr rodzaju dokumentu — tylko znane wartości słownika.
        if (array_key_exists($type, Authorization::TYPES)) {
            $query->where('type', $type);
        } else {
            $type = '';
        }

        $authorizations = $query->paginate(15)->withQueryString();

        return view('authorizations.index', [
            'authorizations' => $authorizations,
            'q'              => $q,
            'type'           => $type,
            'types'          => Authorization::TYPES,
        ]);
    }
}
