<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\SiteSetting;

/**
 * Wszystkie teksty modułu „Fundraising i darowizny” w jednym rejestrze:
 * nagłówki, opisy, etykiety pól i przycisków, komunikaty, strony podziękowań,
 * e-mail z podziękowaniem i potwierdzenie PDF.
 *
 * Kolejność ustalania wartości: nadpisanie w kampanii (campaigns.texts) →
 * ustawienie globalne witryny (site_settings.fundraising_texts) → wartość
 * domyślna z DEFS. Formularze w panelu (globalny i w kampanii) budują się
 * z tego samego rejestru, więc każdy nowy tekst od razu jest edytowalny.
 *
 * Zmienne w tekstach: {kwota}, {kampania}, {imie}, {organizacja}, {data},
 * {konto}, {cel}, {zebrano}.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
final class FundraisingTexts
{
    /** Grupy pól w panelu: klucz => [nazwa, opis]. */
    public const GROUPS = [
        'list' => ['Lista zbiórek', 'Strona /kampanie z kafelkami zbiórek.'],
        'campaign' => ['Strona zbiórki', 'Pasek postępu, przyciski i lista ostatnich wpłat.'],
        'form' => ['Formularz wpłaty', 'Etykiety pól, przycisk i komunikaty formularza.'],
        'success' => ['Podziękowanie: wpłata przyjęta', 'Strona po udanej płatności.'],
        'pending' => ['Podziękowanie: płatność w toku', 'Gdy operator jeszcze nie potwierdził wpłaty.'],
        'failed' => ['Podziękowanie: płatność odrzucona', 'Gdy płatność się nie udała.'],
        'email' => ['E-mail z podziękowaniem', 'Wysyłany automatycznie po zaksięgowaniu wpłaty.'],
        'pdf' => ['Potwierdzenie PDF', 'Dokument do pobrania ze strony podziękowania.'],
    ];

    /**
     * Klucz => [grupa, etykieta w panelu, typ pola (line|text|url), wartość domyślna].
     */
    public const DEFS = [
        // Lista zbiórek
        'list_title' => ['list', 'Nagłówek strony (H1)', 'line', 'Zbiórki'],
        'list_intro' => ['list', 'Tekst wstępu', 'text', 'Wesprzyj nasze działania. Każda wpłata pomaga nam pomagać.'],
        'list_featured_label' => ['list', 'Podpis wyróżnionej zbiórki', 'line', 'Wyróżniona zbiórka'],
        'list_cta' => ['list', 'Przycisk na kafelku', 'line', 'Zobacz zbiórkę'],
        'list_empty' => ['list', 'Komunikat, gdy nie ma zbiórek', 'line', 'Aktualnie nie prowadzimy żadnych zbiórek.'],
        'list_finished_heading' => ['list', 'Nagłówek zakończonych zbiórek (H2)', 'line', 'Zakończone zbiórki'],
        'general_purpose' => ['list', 'Nazwa celu darowizny ogólnej (bez zbiórki)', 'line', 'cele statutowe'],
        'new_tab_sr' => ['list', 'Dopisek dla czytników przy linkach w nowej karcie', 'line', '(otwiera się w nowej karcie)'],

        // Strona zbiórki
        'progress_collected' => ['campaign', 'Podpis pod zebraną kwotą', 'line', 'zebrano z celu {cel}'],
        'progress_no_goal' => ['campaign', 'Podpis, gdy zbiórka nie ma celu', 'line', 'tyle już zebraliśmy'],
        'goal_reached' => ['campaign', 'Komunikat po osiągnięciu celu', 'line', 'Cel zbiórki został osiągnięty. Dziękujemy!'],
        'ends_label' => ['campaign', 'Data zakończenia', 'line', 'Zbiórka trwa do {data}'],
        'closed_message' => ['campaign', 'Komunikat zakończonej zbiórki', 'text', 'Ta zbiórka jest zakończona. Dziękujemy wszystkim, którzy ją wsparli.'],
        'support_heading' => ['campaign', 'Nagłówek sekcji wpłaty (H2)', 'line', 'Wesprzyj zbiórkę'],
        'wplacam_cta' => ['campaign', 'Przycisk do wplacam.ngo.pl', 'line', 'Wpłać przez wplacam.ngo.pl'],
        'wplacam_embed_title' => ['campaign', 'Tytuł osadzonego formularza wplacam.ngo.pl', 'line', 'Formularz wpłaty wplacam.ngo.pl'],
        'wplacam_note' => ['campaign', 'Opis przy wpłacie przez wplacam.ngo.pl', 'text', 'Wpłatę obsługuje serwis wplacam.ngo.pl. Kwota z tego serwisu pojawia się na pasku po aktualizacji przez nasz zespół.'],
        'external_cta' => ['campaign', 'Przycisk do zewnętrznej zbiórki', 'line', 'Wesprzyj zbiórkę'],
        'recent_heading' => ['campaign', 'Nagłówek ostatnich wpłat (H2)', 'line', 'Ostatnie wpłaty'],
        'recent_empty' => ['campaign', 'Komunikat, gdy nie ma wpłat', 'line', 'Bądź pierwszą osobą, która wesprze tę zbiórkę.'],
        'recent_anonymous' => ['campaign', 'Podpis wpłaty anonimowej', 'line', 'Wpłata anonimowa'],
        'progress_aria_label' => ['campaign', 'Nazwa paska postępu dla czytników', 'line', '{kampania}: postęp zbiórki'],
        'progress_aria_valuetext' => ['campaign', 'Wartość paska postępu dla czytników', 'line', '{zebrano} z {cel}'],
        'back_link' => ['campaign', 'Link powrotu do listy', 'line', 'Wróć do listy zbiórek'],

        // Formularz
        'form_heading' => ['form', 'Nagłówek formularza (H2)', 'line', 'Wpłać online'],
        'form_intro' => ['form', 'Tekst nad formularzem', 'text', ''],
        'form_required_note' => ['form', 'Informacja o polach wymaganych', 'line', 'Pola oznaczone gwiazdką (*) są wymagane.'],
        'optional_marker' => ['form', 'Oznaczenie pola opcjonalnego', 'line', '(opcjonalnie)'],
        'amount_legend' => ['form', 'Nagłówek wyboru kwoty', 'line', 'Wybierz kwotę'],
        'amount_other_option' => ['form', 'Przycisk „inna kwota”', 'line', 'Inna kwota'],
        'amount_other_label' => ['form', 'Etykieta pola własnej kwoty', 'line', 'Twoja kwota (zł)'],
        'amount_other_help' => ['form', 'Podpowiedź pod własną kwotą', 'line', 'Od {min} do {max} zł.'],
        'type_legend' => ['form', 'Nagłówek wyboru rodzaju wpłaty', 'line', 'Jak chcesz wesprzeć?'],
        'type_one_time' => ['form', 'Wpłata jednorazowa', 'line', 'Jednorazowo'],
        'type_recurring' => ['form', 'Wpłata cykliczna', 'line', 'Co miesiąc'],
        'recurring_note' => ['form', 'Opis wpłaty cyklicznej', 'text', 'Pierwszą wpłatę zrobisz teraz online. Kolejne najprościej ustawić jako zlecenie stałe w banku — dane wyślemy w e-mailu z podziękowaniem.'],
        'donor_legend' => ['form', 'Nagłówek danych darczyńcy', 'line', 'Twoje dane'],
        'first_name_label' => ['form', 'Etykieta: imię', 'line', 'Imię'],
        'last_name_label' => ['form', 'Etykieta: nazwisko', 'line', 'Nazwisko'],
        'email_label' => ['form', 'Etykieta: e-mail', 'line', 'E-mail'],
        'phone_label' => ['form', 'Etykieta: telefon', 'line', 'Numer telefonu'],
        'company_toggle' => ['form', 'Pole wyboru: wpłata firmy', 'line', 'Wpłacam w imieniu firmy lub organizacji'],
        'company_label' => ['form', 'Etykieta: nazwa firmy', 'line', 'Nazwa firmy lub organizacji'],
        'nip_label' => ['form', 'Etykieta: NIP', 'line', 'NIP'],
        'dedication_label' => ['form', 'Etykieta: dedykacja lub komentarz', 'line', 'Dedykacja lub komentarz'],
        'dedication_help' => ['form', 'Podpowiedź pod dedykacją', 'line', 'Np. „Dla Mamy w dniu urodzin”. Do 300 znaków.'],
        'dedication_public_label' => ['form', 'Pole wyboru: pokaż dedykację', 'line', 'Pokaż dedykację na liście wpłat'],
        'visibility_legend' => ['form', 'Nagłówek widoczności na liście wpłat', 'line', 'Lista „Ostatnie wpłaty”'],
        'visibility_name' => ['form', 'Opcja: pokaż imię', 'line', 'Pokaż moje imię i pierwszą literę nazwiska'],
        'visibility_anonymous' => ['form', 'Opcja: wpłata anonimowa', 'line', 'Wpłata anonimowa'],
        'consents_legend' => ['form', 'Nagłówek zgód', 'line', 'Zgody'],
        'rodo_info' => ['form', 'Informacja o administratorze danych', 'text', 'Administratorem danych osobowych jest {organizacja}. Dane przetwarzamy w celu przyjęcia i rozliczenia darowizny oraz, jeśli wyrazisz zgodę, wysyłki newslettera.'],
        'consent_rodo_label' => ['form', 'Zgoda na przetwarzanie danych', 'text', 'Zapoznałem/-am się z Polityką prywatności i wyrażam zgodę na przetwarzanie moich danych osobowych w celu przyjęcia darowizny.'],
        'privacy_link_label' => ['form', 'Napis linku do polityki prywatności', 'line', 'Polityka prywatności'],
        'privacy_link_url' => ['form', 'Adres polityki prywatności', 'line', '/polityka-prywatnosci'],
        'consent_newsletter_label' => ['form', 'Zgoda na newsletter', 'text', 'Chcę otrzymywać newsletter (opcjonalnie; zgodę można w każdej chwili wycofać).'],
        'submit_label' => ['form', 'Przycisk wysyłki', 'line', 'Wpłacam'],
        'payment_note' => ['form', 'Informacja pod przyciskiem', 'text', 'Po kliknięciu przejdziesz do bezpiecznej płatności w serwisie Przelewy24 (BLIK, szybki przelew, karta). Nie przechowujemy danych Twojej karty ani banku.'],
        'errors_heading' => ['form', 'Nagłówek listy błędów', 'line', 'Popraw zaznaczone pola:'],
        'error_amount' => ['form', 'Błąd: kwota spoza listy', 'line', 'Wybierz kwotę z listy albo wpisz własną.'],
        'error_amount_range' => ['form', 'Błąd: kwota poza zakresem', 'line', 'Kwota musi wynosić od {min} do {max} zł.'],
        'error_nip' => ['form', 'Błąd: NIP', 'line', 'Wpisz poprawny NIP (10 cyfr).'],
        'error_consent' => ['form', 'Błąd: brak zgody', 'line', 'Zaznacz zgodę na przetwarzanie danych osobowych.'],
        'error_phone' => ['form', 'Błąd: numer telefonu', 'line', 'Numer telefonu może zawierać tylko cyfry, spacje i znaki + ( ) -.'],
        'error_gateway' => ['form', 'Błąd: brak połączenia z operatorem płatności', 'text', 'Nie udało się połączyć z operatorem płatności. Spróbuj ponownie za chwilę.'],
        'error_processing' => ['form', 'Komunikat: płatność jeszcze przetwarzana (przy ponowieniu)', 'text', 'Operator płatności nadal przetwarza Twoją wpłatę. Nie płać drugi raz — sprawdź tę stronę za kilka minut.'],
        'payment_description' => ['form', 'Opis transakcji u operatora płatności', 'line', 'Darowizna — {kampania}'],
        'unavailable' => ['form', 'Komunikat: płatności niedostępne', 'text', 'Płatności online są chwilowo niedostępne. Możesz przekazać darowiznę tradycyjnym przelewem.'],

        // Sukces
        'success_title' => ['success', 'Nagłówek (H1)', 'line', 'Dziękujemy, {imie}!'],
        'success_lead' => ['success', 'Podtytuł', 'line', 'Twoja wpłata na zbiórkę „{kampania}” dotarła.'],
        'success_body' => ['success', 'Treść', 'text', 'Każda złotówka przybliża nas do celu. Potwierdzenie wysłaliśmy na Twój adres e-mail.'],
        'success_image_alt' => ['success', 'Opis alternatywny zdjęcia (ALT)', 'line', ''],
        'success_video_url' => ['success', 'Film z podziękowaniem (adres YouTube lub Vimeo)', 'url', ''],
        'success_video_title' => ['success', 'Tytuł filmu (dla czytników ekranu)', 'line', 'Film z podziękowaniem'],
        'summary_heading' => ['success', 'Nagłówek podsumowania (H2)', 'line', 'Podsumowanie wpłaty'],
        'summary_amount' => ['success', 'Podsumowanie: etykieta kwoty', 'line', 'Kwota'],
        'summary_purpose' => ['success', 'Podsumowanie: etykieta celu', 'line', 'Cel'],
        'summary_type' => ['success', 'Podsumowanie: etykieta rodzaju', 'line', 'Rodzaj'],
        'summary_date' => ['success', 'Podsumowanie: etykieta daty', 'line', 'Data'],
        'summary_company' => ['success', 'Podsumowanie: etykieta firmy', 'line', 'Firma'],
        'summary_nip' => ['success', 'Podsumowanie: etykieta NIP', 'line', 'NIP'],
        'recurring_thanks' => ['success', 'Informacja dla wpłat cyklicznych', 'text', 'Zadeklarowałeś/-aś wsparcie co miesiąc. Ustaw w banku zlecenie stałe na kwotę {kwota} na rachunek {konto}, tytuł: „Darowizna — {kampania}”.'],
        'share_heading' => ['success', 'Nagłówek udostępniania (H2)', 'line', 'Powiedz o zbiórce znajomym'],
        'share_text' => ['success', 'Tekst do udostępnienia', 'line', 'Wsparłem/-am zbiórkę „{kampania}”. Dołącz!'],
        'share_email_label' => ['success', 'Udostępnianie: napis przycisku e-mail', 'line', 'E-mail'],
        'share_email_subject' => ['success', 'Udostępnianie: temat e-maila', 'line', '{kampania}'],
        'share_sr' => ['success', 'Udostępnianie: dopisek dla czytników', 'line', '(udostępnij, otwiera się w nowej karcie)'],
        'tax_heading' => ['success', 'Nagłówek: ulga podatkowa (H2)', 'line', 'Odliczenie od podatku'],
        'tax_body' => ['success', 'Treść: ulga podatkowa', 'text', 'Darowiznę na cele pożytku publicznego możesz odliczyć w zeznaniu rocznym (do 6% dochodu, osoby prawne do 10%). Zachowaj potwierdzenie wpłaty.'],
        'pdf_cta' => ['success', 'Przycisk pobrania potwierdzenia', 'line', 'Pobierz potwierdzenie (PDF)'],
        'home_cta' => ['success', 'Link do strony głównej', 'line', 'Wróć na stronę główną'],

        // W toku
        'pending_title' => ['pending', 'Nagłówek (H1)', 'line', 'Czekamy na potwierdzenie płatności'],
        'pending_body' => ['pending', 'Treść', 'text', 'Operator płatności zwykle potwierdza wpłatę w ciągu kilku sekund. Jeśli płatność się udała, nic więcej nie musisz robić. Dziękujemy!'],
        'pending_refresh' => ['pending', 'Przycisk odświeżenia', 'line', 'Sprawdź ponownie'],

        // Odrzucona
        'failed_title' => ['failed', 'Nagłówek (H1)', 'line', 'Płatność nie doszła do skutku'],
        'failed_body' => ['failed', 'Treść', 'text', 'Nie pobraliśmy żadnych środków. Możesz spróbować jeszcze raz albo przekazać darowiznę tradycyjnym przelewem.'],
        'retry_cta' => ['failed', 'Przycisk ponowienia płatności', 'line', 'Ponów płatność'],

        // E-mail
        'email_subject' => ['email', 'Temat wiadomości', 'line', 'Dziękujemy za wsparcie, {imie}!'],
        'email_heading' => ['email', 'Nagłówek wiadomości', 'line', 'Dziękujemy za Twoją wpłatę'],
        'email_body' => ['email', 'Treść wiadomości', 'text', "Otrzymaliśmy Twoją darowiznę w wysokości {kwota} na zbiórkę „{kampania}”.\n\nDzięki Tobie możemy działać dalej. Potwierdzenie wpłaty możesz pobrać z odnośnika poniżej."],
        'email_image_alt' => ['email', 'Opis alternatywny grafiki (ALT)', 'line', ''],
        'email_cta' => ['email', 'Odnośnik do potwierdzenia', 'line', 'Pobierz potwierdzenie wpłaty (PDF)'],
        'email_signature' => ['email', 'Podpis', 'text', 'Zespół {organizacja}'],

        // PDF
        'pdf_title' => ['pdf', 'Tytuł dokumentu', 'line', 'Potwierdzenie darowizny'],
        'pdf_body' => ['pdf', 'Treść', 'text', 'Potwierdzamy otrzymanie darowizny na cele statutowe {organizacja}. Dokument może służyć jako potwierdzenie do odliczenia darowizny od dochodu (art. 26 ust. 1 pkt 9 ustawy o PIT, art. 18 ust. 1 pkt 1 ustawy o CIT) razem z potwierdzeniem przelewu.'],
        'pdf_number_label' => ['pdf', 'Etykieta: numer potwierdzenia', 'line', 'Numer potwierdzenia'],
        'pdf_donor_label' => ['pdf', 'Etykieta: darczyńca', 'line', 'Darczyńca'],
        'pdf_nip_label' => ['pdf', 'Etykieta: NIP darczyńcy', 'line', 'NIP darczyńcy'],
        'pdf_amount_label' => ['pdf', 'Etykieta: kwota', 'line', 'Kwota'],
        'pdf_date_label' => ['pdf', 'Etykieta: data wpłaty', 'line', 'Data wpłaty'],
        'pdf_purpose_label' => ['pdf', 'Etykieta: cel', 'line', 'Cel'],
        'pdf_method_label' => ['pdf', 'Etykieta: sposób wpłaty', 'line', 'Sposób wpłaty'],
        'pdf_method_online' => ['pdf', 'Sposób wpłaty: online', 'line', 'Płatność online (Przelewy24)'],
        'pdf_method_manual' => ['pdf', 'Sposób wpłaty: przelew', 'line', 'Przelew lub wpłata przyjęta przez organizację'],
        'pdf_transaction_label' => ['pdf', 'Dopisek: numer transakcji', 'line', 'nr transakcji'],
        'pdf_filename' => ['pdf', 'Nazwa pobieranego pliku (bez .pdf)', 'line', 'potwierdzenie-darowizny'],
        'pdf_footer' => ['pdf', 'Stopka', 'text', 'Dziękujemy za wsparcie!'],
    ];

    /** Grupy, których teksty można nadpisać w pojedynczej kampanii (bez listy zbiórek). */
    public const CAMPAIGN_GROUPS = ['campaign', 'form', 'success', 'pending', 'failed', 'email', 'pdf'];

    private array $global;

    private array $override;

    private function __construct(private readonly ?Campaign $campaign)
    {
        $this->global = self::clean(SiteSetting::current()->fundraising_texts ?? []);
        $this->override = $campaign ? self::clean($campaign->texts ?? []) : [];
    }

    public static function for(?Campaign $campaign = null): self
    {
        return new self($campaign);
    }

    /** Tekst z podstawionymi zmiennymi (zwykły tekst — w widoku zawsze przez {{ }}). */
    public function get(string $key, array $vars = []): string
    {
        $value = $this->override[$key] ?? $this->global[$key] ?? (self::DEFS[$key][3] ?? '');

        return $this->fill($value, $vars);
    }

    /** Tekst wielowierszowy podzielony na akapity (pusta linia = nowy akapit). */
    public function paragraphs(string $key, array $vars = []): array
    {
        $text = trim($this->get($key, $vars));

        return $text === '' ? [] : preg_split('/\R{2,}/', $text);
    }

    /** Wartość globalna (bez nadpisania w kampanii) — podpowiedź w formularzu kampanii. */
    public function globalValue(string $key): string
    {
        return $this->global[$key] ?? (self::DEFS[$key][3] ?? '');
    }

    /** Zmienne wspólne dla wpłaty/kampanii. */
    public function vars(?\App\Models\Donation $donation = null): array
    {
        $settings = SiteSetting::current();
        $campaign = $this->campaign;

        return [
            'organizacja' => (string) $settings->site_name,
            'konto' => (string) $settings->bank_account_number,
            'kampania' => $campaign?->title ?? $this->get('general_purpose'),
            'cel' => $campaign ? Money::zl($campaign->goal_amount) : '',
            'zebrano' => $campaign ? Money::zl($campaign->collected_amount) : '',
            'data' => $campaign?->ends_at?->format('d.m.Y') ?? '',
            'kwota' => $donation?->amountLabel() ?? '',
            'imie' => $donation ? ($donation->first_name ?: $donation->company_name ?: '') : '',
        ];
    }

    /** Pola jednej grupy: klucz => [etykieta, typ, domyślna]. */
    public static function fields(string $group): array
    {
        $out = [];
        foreach (self::DEFS as $key => [$g, $label, $type, $default]) {
            if ($g === $group) {
                $out[$key] = [$label, $type, $default];
            }
        }

        return $out;
    }

    /** Reguły walidacji tablicy tekstów z formularza (`texts[klucz]`). */
    public static function rules(string $prefix = 'texts'): array
    {
        $rules = [$prefix => ['nullable', 'array']];
        foreach (self::DEFS as $key => [, , $type]) {
            $rules["{$prefix}.{$key}"] = match ($type) {
                'line' => ['nullable', 'string', 'max:255'],
                'url' => ['nullable', 'url', 'max:500', 'regex:~^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)/~i'],
                default => ['nullable', 'string', 'max:4000'],
            };
        }

        return $rules;
    }

    /** Zostawia tylko znane klucze z niepustą wartością (puste = dziedzicz). */
    public static function clean(mixed $texts): array
    {
        if (! is_array($texts)) {
            return [];
        }

        $out = [];
        foreach ($texts as $key => $value) {
            if (isset(self::DEFS[$key]) && is_string($value) && trim($value) !== '') {
                $out[$key] = trim(str_replace("\r\n", "\n", $value));
            }
        }

        return $out;
    }

    /**
     * Adres osadzenia filmu z podziękowaniem (YouTube/Vimeo, bez śledzenia) albo null.
     */
    public function videoEmbedUrl(): ?string
    {
        $url = $this->get('success_video_url');
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?dnt=1';
        }

        return null;
    }

    /** Zdjęcie strony podziękowania: z kampanii, a gdy brak — globalne. */
    public function imageUrl(string $kind): ?string
    {
        $collection = $kind === 'email' ? 'thanks_email_image' : 'thanks_image';

        if ($this->campaign && ($url = $this->campaign->getFirstMediaUrl($collection))) {
            return $url;
        }

        return SiteSetting::current()->getFirstMediaUrl('fundraising_'.$collection) ?: null;
    }

    private function fill(string $value, array $vars): string
    {
        if ($vars === [] || ! str_contains($value, '{')) {
            return $value;
        }

        $map = [];
        foreach ($vars as $k => $v) {
            $map['{'.$k.'}'] = (string) $v;
        }

        return strtr($value, $map);
    }
}
