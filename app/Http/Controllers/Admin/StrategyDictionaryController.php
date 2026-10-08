<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StrategyActionType;
use App\Models\StrategyFundingSource;
use App\Models\StrategyStatuteRef;
use App\Models\StrategyTargetGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Panel admin: zarządzanie słownikami modułu „Strategia organizacji"
 * (grupy docelowe, typy działań, punkty statutu, źródła finansowania).
 * Jeden kontroler obsługuje cztery słowniki — wybór przez parametr {dictionary}.
 * Dostęp ograniczony do administratorów (middleware w trasach).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class StrategyDictionaryController extends Controller
{
    /** Mapa: identyfikator słownika w URL → klasa modelu. */
    private const DICTIONARIES = [
        'grupy'        => StrategyTargetGroup::class,
        'typy'         => StrategyActionType::class,
        'statut'       => StrategyStatuteRef::class,
        'finansowanie' => StrategyFundingSource::class,
    ];

    /** Dodaje pozycję do wskazanego słownika. */
    public function store(Request $request, string $dictionary): JsonResponse
    {
        $model = $this->modelClass($dictionary);
        $data = $this->validated($request, $dictionary);
        $data['position'] = ((int) $model::max('position')) + 1;

        $item = $model::create($data);

        return response()->json([
            'message' => 'Pozycja słownika została dodana.',
            'item'    => $item,
        ], 201);
    }

    /** Aktualizuje pozycję słownika (nazwa, aktywność). */
    public function update(Request $request, string $dictionary, int $id): JsonResponse
    {
        $item = $this->find($dictionary, $id);
        $item->update($this->validated($request, $dictionary));

        return response()->json([
            'message' => 'Pozycja słownika została zaktualizowana.',
            'item'    => $item->fresh(),
        ]);
    }

    /**
     * Usuwa pozycję słownika. Pozycje użyte w planach nie są usuwane —
     * zamiast tego zwracamy komunikat z sugestią dezaktywacji.
     */
    public function destroy(string $dictionary, int $id): JsonResponse
    {
        $item = $this->find($dictionary, $id);

        if ($item->plans()->exists()) {
            return response()->json([
                'message' => 'Ta pozycja jest używana w istniejących planach — zamiast usuwać, oznacz ją jako nieaktywną.',
            ], 409);
        }

        $item->delete();

        return response()->json(['message' => 'Pozycja słownika została usunięta.']);
    }

    /** Rozwiązuje klasę modelu słownika albo przerywa 404. */
    private function modelClass(string $dictionary): string
    {
        return self::DICTIONARIES[$dictionary] ?? abort(404, 'Nieznany słownik.');
    }

    private function find(string $dictionary, int $id): Model
    {
        return $this->modelClass($dictionary)::findOrFail($id);
    }

    /**
     * Reguły walidacji zależne od słownika. Błędy zwracane są jako JSON 422
     * (globalny handler renderuje JSON tylko dla api/*, a tu mówimy fetch-em).
     */
    private function validated(Request $request, string $dictionary): array
    {
        $rules = [
            'name'      => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];

        if ($dictionary === 'statut') {
            $rules = [
                'code'      => ['required', 'string', 'max:50'],
                'title'     => ['required', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ];
        } elseif ($dictionary === 'finansowanie') {
            $rules['kind'] = ['required', Rule::in(array_keys(StrategyFundingSource::KINDS))];
        } elseif ($dictionary === 'typy') {
            $rules['icon'] = ['nullable', 'string', 'max:50'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422));
        }

        return $validator->validated();
    }
}
