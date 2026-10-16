<?php

namespace App\Support;

use App\Models\User;

/**
 * Uprawnienia modułu Newsletter — drobniejsze niż sam dostęp do modułu.
 *
 * Klucze (np. `newsletter.send`) są jednocześnie nazwami bramek (Gate, middleware `can:`)
 * i wartościami zapisywanymi w `user_groups.modules`, więc grupa użytkowników może dostać
 * wybrane uprawnienia tak samo jak moduły treści. Administrator ma wszystkie; role
 * „Redaktor newslettera” i „Wydawca newslettera” mają stałe zestawy (ROLE_PRESETS).
 *
 * Każde uprawnienie zawiera w sobie przeglądanie (VIEW) — bez niego nie da się wejść do modułu.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
final class NewsletterPermissions
{
    public const VIEW        = 'newsletter.view';
    public const CAMPAIGNS   = 'newsletter.campaigns';
    public const SEND        = 'newsletter.send';
    public const TEMPLATES   = 'newsletter.templates';
    public const SUBSCRIBERS = 'newsletter.subscribers';
    public const REPORTS     = 'newsletter.reports';
    public const SETTINGS    = 'newsletter.settings';

    /** Bramka pomocnicza: edytor Mosaico i jego zasoby (obrazy, treści) — dla kampanii albo szablonów. */
    public const DESIGN = 'newsletter.design';

    /** Uprawnienia do nadania w grupie: klucz => [etykieta, opis]. */
    public const ALL = [
        self::VIEW        => ['Przeglądanie newslettera', 'Pulpit, lista i podgląd kampanii — bez zmian.'],
        self::CAMPAIGNS   => ['Kampanie: tworzenie i edycja', 'Nowe kampanie, treść w edytorze Mosaico, wysyłka testowa, duplikowanie, usuwanie szkiców.'],
        self::SEND        => ['Kampanie: wysyłka', 'Wysłanie, zaplanowanie, wstrzymanie, wznowienie i anulowanie wysyłki. Wysyłki nie da się cofnąć.'],
        self::TEMPLATES   => ['Szablony i maile systemowe', 'Szablony wiadomości i maile systemowe (np. potwierdzenie zapisu).'],
        self::SUBSCRIBERS => ['Subskrybenci, listy i segmenty', 'Dane osobowe: subskrybenci, import, eksport, anonimizacja, listy, segmenty, tematy i formularze zapisu.'],
        self::REPORTS     => ['Raporty kampanii', 'Statystyki otwarć i kliknięć, lista odbiorców i eksport CSV raportu.'],
        self::SETTINGS    => ['Ustawienia newslettera', 'Nadawca, serwer poczty, zgody i integracje.'],
    ];

    /** Stałe zestawy uprawnień ról (administrator ma wszystko — poza tą listą). */
    public const ROLE_PRESETS = [
        User::ROLE_NEWSLETTER_EDITOR => [self::VIEW, self::CAMPAIGNS, self::TEMPLATES, self::REPORTS],
        User::ROLE_NEWSLETTER_PUBLISHER => [self::VIEW, self::CAMPAIGNS, self::SEND, self::TEMPLATES, self::SUBSCRIBERS, self::REPORTS],
        // Edytor treści miał dotąd pełny dostęp do modułu — zostaje, ale bez ustawień (jak w pozostałych modułach).
        User::ROLE_CONTENT_EDITOR => [self::VIEW, self::CAMPAIGNS, self::SEND, self::TEMPLATES, self::SUBSCRIBERS, self::REPORTS],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    /**
     * Uprawnienia newslettera użytkownika (administrator — wszystkie).
     *
     * @return list<string>
     */
    public static function for(User $user): array
    {
        if ($user->isAdmin()) {
            return self::keys();
        }

        $granted = self::ROLE_PRESETS[$user->role]
            ?? ($user->role === User::ROLE_EDITOR && $user->group
                ? array_values(array_intersect(self::keys(), $user->group->modules ?? []))
                : []);

        if ($granted !== [] && ! in_array(self::VIEW, $granted, true)) {
            $granted[] = self::VIEW;
        }

        return $granted;
    }

    public static function allows(User $user, string $permission): bool
    {
        if ($permission === self::DESIGN) {
            return self::allows($user, self::CAMPAIGNS) || self::allows($user, self::TEMPLATES);
        }

        return in_array($permission, self::for($user), true);
    }
}
