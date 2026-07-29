<?php

namespace App\Http\Controllers\Concerns;

/**
 * Obieg akceptacji treści. Użytkownik z uprawnieniem akceptanta (lub admin)
 * publikuje od razu. Edytor bez uprawnień: próba publikacji trafia do kolejki
 * „Do zatwierdzenia" (pozostaje nieopublikowane do czasu akceptacji).
 */
trait HandlesContentApproval
{
    protected function applyApprovalWorkflow(array $data): array
    {
        $user = auth()->user();

        if ($user->canApproveContent()) {
            $data['pending_approval'] = false;

            return $data;
        }

        if (! empty($data['is_published'])) {
            $data['is_published'] = false;
            $data['pending_approval'] = true;
        } else {
            $data['pending_approval'] = false;
        }

        $data['submitted_by_id'] = $user->id;

        return $data;
    }
}
