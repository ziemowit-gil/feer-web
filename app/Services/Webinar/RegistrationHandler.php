<?php

namespace App\Services\Webinar;

use App\Models\LandingPage;
use App\Models\WebinarRegistration;

/**
 * Kontrakt obsługi zapisu na webinar. Wymianę na integrację z zewnętrznym
 * systemem (np. ClickMeeting, Mailchimp) sprowadza się do podmiany wiązania
 * tego interfejsu w kontenerze (AppServiceProvider) — reszta kodu bez zmian.
 */
interface RegistrationHandler
{
    /**
     * @param  array{name:string,email:string,phone:?string,consent:bool}  $data
     */
    public function handle(LandingPage $page, array $data): WebinarRegistration;
}
