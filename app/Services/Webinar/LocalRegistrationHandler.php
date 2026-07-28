<?php

namespace App\Services\Webinar;

use App\Models\LandingPage;
use App\Models\WebinarRegistration;

/**
 * Domyślna implementacja: zapis lokalny w bazie. Metoda forward() to
 * wydzielone miejsce na przyszłe wywołanie zewnętrznego API zapisu —
 * dziś nic nie robi, a jej podmiana/rozszerzenie nie dotyka kontrolera.
 */
class LocalRegistrationHandler implements RegistrationHandler
{
    public function handle(LandingPage $page, array $data): WebinarRegistration
    {
        $registration = $page->registrations()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'consent' => (bool) ($data['consent'] ?? false),
        ]);

        if ($this->forward($page, $registration)) {
            $registration->update(['forwarded' => true]);
        }

        return $registration;
    }

    /**
     * Przyszłe podpięcie zewnętrznego systemu zapisu. Zwróć true po udanym
     * przekazaniu. Przykład (do uzupełnienia):
     *
     *   return Http::withToken(config('services.webinar.token'))
     *       ->post(config('services.webinar.endpoint'), [...])
     *       ->successful();
     */
    protected function forward(LandingPage $page, WebinarRegistration $registration): bool
    {
        return false;
    }
}
