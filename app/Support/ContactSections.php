<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Sekcje dodatkowe strony kontaktu w szablonie FEER, wyświetlane jako automatyczne podstrony
 * (/kontakt/spotkania, /kontakt/rachunki…). Dane pochodzą z ustawień (Ustawienia → Kontakt),
 * więc podstrona istnieje tylko wtedy, gdy jest co na niej pokazać.
 */
class ContactSections
{
    /** Adresy podstron w kolejności wyświetlania w menu bocznym. */
    public const SLUGS = ['spotkania', 'przesylki', 'rachunki', 'oddzialy', 'dane-rejestrowe'];

    /**
     * Dostępne podstrony: [slug => etykieta].
     *
     * @return array<string, string>
     */
    public static function available(SiteSetting $s, bool $showMeetings, bool $showShipping): array
    {
        $has = [
            'spotkania' => $showMeetings,
            'przesylki' => $showShipping,
            'rachunki' => ! empty($s->contact_bank_accounts) || filled($s->contact_bank_accounts_note),
            'oddzialy' => (bool) $s->contactBranches(),
            'dane-rejestrowe' => filled($s->krs_number) || filled($s->nip_number) || filled($s->regon_number),
        ];
        $labels = [
            'spotkania' => $s->contact_meeting_title ?: 'Spotkania',
            'przesylki' => 'Przesyłki',
            'rachunki' => 'Numery rachunków',
            'oddzialy' => $s->contact_branches_title ?: 'Oddziały',
            'dane-rejestrowe' => 'Dane rejestrowe',
        ];

        $out = [];
        foreach (self::SLUGS as $slug) {
            if ($has[$slug]) {
                $out[$slug] = $labels[$slug];
            }
        }

        return $out;
    }
}
