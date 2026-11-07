<?php

namespace Modules\Contact\Support;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use App\Modules\ModuleManager;

/**
 * Sekcje dodatkowe strony kontaktu w szablonie FEER, wyświetlane jako automatyczne podstrony
 * (/kontakt/spotkania, /kontakt/rachunki…). Dane pochodzą z ustawień (Ustawienia → Kontakt),
 * więc podstrona istnieje tylko wtedy, gdy jest co na niej pokazać — i tylko przy aktywnym module Kontakt.
 */
class ContactSections
{
    /** Adresy podstron w kolejności wyświetlania w menu bocznym. */
    public const SLUGS = ['spotkania', 'przesylki', 'rachunki', 'oddzialy', 'dane-rejestrowe'];

    /** Typy stron książki telefonicznej w kontakcie: nowy z modułu i dawny (do czasu przeniesienia stron). */
    public const BOOK_TYPES = ['contact_book', 'phone_book'];

    /** Czy moduł Kontakt jest aktywny (wbudowany: domyślnie tak, także zanim istnieje tabela plugins). */
    public static function moduleActive(): bool
    {
        return app(ModuleManager::class)->isActive('contact');
    }

    /** Czy ustawienia mają dane do sekcji „Spotkania” (link do rezerwacji, harmonogram albo notatka o pracy zdalnej). */
    public static function hasMeetings(SiteSetting $s): bool
    {
        return filled($s->contact_online_meeting_url) || ! empty($s->contactScheduleUpcoming()) || filled($s->contact_remote_note);
    }

    /** Czy ustawienia mają dane do sekcji „Przesyłki” (notatka, paczkomat albo telefon do przesyłek). */
    public static function hasShipping(SiteSetting $s): bool
    {
        return filled($s->contact_shipping_note) || filled($s->contact_paczkomat_code)
            || filled($s->contact_paczkomat_address) || filled($s->contact_shipping_phone);
    }

    /**
     * Dostępne podstrony: [slug => etykieta]. Puste, gdy moduł Kontakt jest wyłączony.
     *
     * @return array<string, string>
     */
    public static function available(SiteSetting $s, bool $showMeetings, bool $showShipping): array
    {
        if (! self::moduleActive()) {
            return [];
        }

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

    /**
     * Książka telefoniczna do podlinkowania ze strony kontaktu: opublikowana strona „Kontakt — książka” (a w drugiej
     * kolejności dawna „Książka telefoniczna”), potem moduł „Książka telefoniczna” (/ksiazka-telefoniczna), gdy ma wpisy.
     * `page_id` to id strony, gdy książka jest stroną (menu boczne nie powtarza jej jako podstrony).
     *
     * @return array{url: string, title: string, page_id: int|null}|null
     */
    public static function phoneBook(SiteSetting $s): ?array
    {
        $page = Page::forCurrentSite()->whereIn('type', self::BOOK_TYPES)->where('is_published', true)
            ->orderByRaw("case type when 'contact_book' then 0 else 1 end")->orderBy('id')->first();
        if ($page) {
            return ['url' => $page->publicUrl(), 'title' => $page->title, 'page_id' => $page->id];
        }

        if ($s->isModuleEnabled('phone_book') && PhoneBookEntry::forCurrentSite()->active()->exists()) {
            return [
                'url' => url('/'.\App\Http\Controllers\PhoneBookController::SLUG),
                'title' => filled($s->phonebook_title) ? $s->phonebook_title : 'Książka telefoniczna',
                'page_id' => null,
            ];
        }

        return null;
    }
}
