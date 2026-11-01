<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Helpdesk;

/**
 * Ręczne odświeżenie statusu licencji z panelu (Ustawienia → Ogólne) — poza
 * automatycznym `helpdesk:sync` co 5 minut (patrz routes/console.php).
 */
class LicenseController extends Controller
{
    /**
     * Wywoływane przez fetch() z panelu (nie zwykły <form> — patrz komentarz w
     * admin/settings/edit.blade.php o zagnieżdżonych formularzach), więc zwraca
     * JSON, a nie przekierowanie z komunikatem flash: strona po stronie JS po
     * prostu odświeża się i tak samo świeżo odczytuje LicenseStatus::current().
     */
    public function check(Helpdesk $helpdesk)
    {
        if (! $helpdesk->enabled()) {
            return response()->json(['ok' => false, 'message' => 'Integracja z Helpdeskiem nieskonfigurowana.'], 422);
        }

        $status = $helpdesk->sync();

        return response()->json([
            'ok' => ! $status->last_error,
            'message' => $status->last_error
                ? "Nie udało się sprawdzić licencji: {$status->last_error}"
                : $status->statusLabel(),
        ]);
    }
}
