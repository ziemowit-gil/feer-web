<?php

namespace App\Models;

/**
 * Rozszerzenie spatie/laravel-activitylog o polskie etykiety zdarzeń i typów treści
 * oraz wirtualne właściwości kompatybilne z widokami BIP i dziennika admina.
 */
class Activity extends \Spatie\Activitylog\Models\Activity
{
    public const EVENTS = [
        'created'           => 'Utworzenie',
        'updated'           => 'Edycja',
        'deleted'           => 'Usunięcie',
        'restored'          => 'Przywrócenie',
        'force_deleted'     => 'Trwałe usunięcie',
        'reset_votes'       => 'Zerowanie głosów',
        'approved'          => 'Zatwierdzenie',
        'rejected'          => 'Odrzucenie',
        'bulk_published'    => 'Publikacja masowa',
        'bulk_unpublished'  => 'Wycofanie masowe',
        'bulk_deleted'      => 'Usunięcie masowe',
        'imported'          => 'Import',
        'exported'          => 'Eksport',
        'settings_updated'  => 'Zmiana ustawień',
        'password_reset'    => 'Reset hasła',
        'microsoft_unlinked'=> 'Odłączenie Microsoft 365',
        'invitation_sent'   => 'Wysłanie zaproszenia',
        // Logowania i konta (dziennik „auth”).
        'login'                 => 'Logowanie',
        'login_failed'          => 'Nieudane logowanie',
        'login_refused'         => 'Odmowa logowania (konto nieaktywne)',
        'logout'                => 'Wylogowanie',
        'logout_expired'        => 'Wylogowanie (konto wygasło)',
        'account_expired'       => 'Wygaszenie konta',
        'impersonation_started' => 'Przełączenie na cudze konto',
        'impersonation_stopped' => 'Powrót na własne konto',
        'groups_changed'        => 'Zmiana grup (uprawnień)',
        'passkey_added'         => 'Dodanie klucza dostępu',
        'passkey_removed'       => 'Usunięcie klucza dostępu',
    ];

    /** Dzienniki do wyboru w panelu. */
    public const LOGS = ['cms' => 'Treści i ustawienia', 'auth' => 'Logowania i konta', 'all' => 'Wszystko'];

    /**
     * Każde zdarzenie dostaje adres IP i — podczas przełączenia na cudze konto — numer administratora, który
     * naprawdę działa (Admin\ImpersonationController). Bez żądania HTTP (cron, kolejka) bez tych danych.
     */
    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }
            $request = request();
            $extra = array_filter([
                'ip' => $request->ip(),
                'impersonator_id' => $request->hasSession() ? $request->session()->get(\App\Http\Controllers\Admin\ImpersonationController::SESSION_KEY) : null,
            ]);
            if ($extra) {
                $activity->properties = collect($activity->properties ?? [])->merge($extra);
            }
        });
    }

    /**
     * Zmienione pola zdarzenia „Edycja” (LogsActivity zapisuje stare i nowe wartości).
     *
     * @return array<int, array{field: string, old: mixed, new: mixed}>
     */
    public function changes(): array
    {
        $old = (array) ($this->properties?->get('old') ?? []);
        $new = (array) ($this->properties?->get('attributes') ?? []);

        return collect(array_unique([...array_keys($old), ...array_keys($new)]))
            ->map(fn ($field) => ['field' => (string) $field, 'old' => $old[$field] ?? null, 'new' => $new[$field] ?? null])
            ->values()->all();
    }

    public const SUBJECTS = [
        'App\\Models\\News'                 => 'Aktualność',
        'App\\Models\\Page'                 => 'Strona',
        'App\\Models\\Project'              => 'Działanie',
        'App\\Models\\LandingPage'          => 'Landing page',
        'App\\Models\\AnnualReport'         => 'Sprawozdanie',
        'App\\Models\\BipDocument'          => 'Dokument BIP',
        'App\\Models\\User'                 => 'Użytkownik',
        'App\\Models\\UserGroup'            => 'Grupa użytkowników',
        'App\\Models\\Task'                 => 'Zadanie',
        'App\\Models\\StrategyPlan'         => 'Plan działania (strategia)',
        'App\\Models\\Authorization'        => 'Wpis rejestru pełnomocnictw',
        'App\\Models\\Event'                => 'Wydarzenie',
        'App\\Models\\Poll'                 => 'Ankieta',
        'App\\Models\\Banner'               => 'Baner',
        'App\\Models\\VolunteerAd'          => 'Oferta wolontariatu',
        'App\\Models\\EducationalMaterial'  => 'Materiał edukacyjny',
        'App\\Models\\Partner'              => 'Partner',
        'App\\Models\\Redirect'             => 'Przekierowanie',
        'App\\Models\\NavItem'              => 'Element nawigacji',
        'App\\Models\\MemberInvitation'     => 'Zaproszenie do strefy',
        'App\\Models\\SiteSetting'          => 'Ustawienia serwisu',
    ];

    /** Polska etykieta zdarzenia. */
    public function eventLabel(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    /** Polska etykieta typu treści na podstawie pełnej nazwy klasy. */
    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject_type] ?? class_basename((string) $this->subject_type);
    }

    /** Tytuł/etykieta obiektu (migawka zapisana w properties). */
    public function getSubjectLabelAttribute(): ?string
    {
        return $this->properties?->get('label');
    }

    /** Nazwa autora zdarzenia (imię lub e-mail) dla widoków. */
    public function getUserNameAttribute(): ?string
    {
        return $this->causer?->name ?? $this->causer?->email;
    }
}
