<?php

namespace App\Http\Controllers\Concerns;

/**
 * Obieg akceptacji treści. Użytkownik z uprawnieniem akceptanta (lub admin) i Samodzielny edytor
 * publikują od razu. Edytor bez uprawnień: próba publikacji trafia do kolejki
 * „Do zatwierdzenia" (pozostaje nieopublikowane do czasu akceptacji).
 */
/**
 * Trait: obieg akceptacji treści — gdy użytkownik nie ma uprawnienia
 * `canApproveContent()`, żądanie publikacji jest kierowane do kolejki oczekujących.
 *
 * Metody: applyApprovalWorkflow().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
trait HandlesContentApproval
{
    /**
     * Stosuje obieg akceptacji do danych formularza: akceptant publikuje od razu,
     * edytor bez uprawnień trafia do kolejki „Do zatwierdzenia" (is_published=false).
     *
     * @param  array  $data  Dane zwalidowanego formularza (może zawierać klucz is_published).
     * @return array         Dane z uzupełnionym pending_approval i ewentualnie submitted_by_id.
     */
    protected function applyApprovalWorkflow(array $data): array
    {
        $user = auth()->user();

        if ($user->canApproveContent()) {
            $data['pending_approval'] = false;

            return $data;
        }

        // Samodzielny edytor publikuje od razu, ale nie zatwierdza cudzych zgłoszeń: zapis bez publikacji
        // zostawia stan oczekiwania bez zmian (np. poprawka literówki w treści czekającej na akceptację).
        if ($user->publishesWithoutApproval()) {
            if (! empty($data['is_published'])) {
                $data['pending_approval'] = false;
            }

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
