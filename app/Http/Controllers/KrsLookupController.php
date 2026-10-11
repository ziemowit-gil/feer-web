<?php

namespace App\Http\Controllers;

use App\Services\KrsClient;
use Illuminate\Http\JsonResponse;

/** Pole „Numer KRS” w formularzach: pobiera dane podmiotu z API KRS (przez serwer — bez CORS). */
class KrsLookupController extends Controller
{
    public function __invoke(string $numer, KrsClient $krs): JsonResponse
    {
        $result = $krs->lookup($numer);

        return match ($result['status']) {
            'found'     => response()->json(['data' => $result['data']]),
            'not_found' => response()->json(['message' => 'Nie znaleziono podmiotu o tym numerze KRS.'], 404),
            default     => response()->json(['message' => 'Rejestr KRS chwilowo nie odpowiada. Uzupełnij dane ręcznie.'], 503),
        };
    }
}
