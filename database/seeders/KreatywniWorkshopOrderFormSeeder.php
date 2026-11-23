<?php

namespace Database\Seeders;

use App\Models\FormDefinition;
use App\Models\KreatywniOffer;
use App\Models\KreatywniOfferItem;
use App\Models\SiteSetting;
use App\Support\KreatywniTexts;
use Illuminate\Database\Seeder;

/**
 * Formularz „Zamówienie warsztatów” w module Formularze (kreator w panelu) dla witryny w szablonie „kreatywni”.
 * Dwa kroki: 1) warsztat, termin, liczba i wiek uczestników, miejsce; 2) zamawiający, kontakt, uwagi i zgoda RODO.
 * Lista „Warsztat” to nazwy widocznych warsztatów z „Mikrooferty – Kreatywni” tej witryny (karty, a bez nich tekst
 * oferty), a gdy witryna nie ma oferty — domyślna lista szablonu; na końcu „Nie wiem jeszcze — doradźcie”.
 * Powiadomienia idą na e-mail kontaktowy witryny (albo NOTIFY_EMAIL), z odpowiedzią do zamawiającego i kopią dla niego.
 * Oferty witryny z pustym adresem przycisku („Zamów warsztaty”) dostają adres formularza — wypełnionych nie zmienia.
 *
 * Uruchom: SITE_SLUG=kreatywni php artisan db:seed --class=KreatywniWorkshopOrderFormSeeder --force
 * (na serwerze php84; bez SITE_SLUG — pierwsza witryna w szablonie „kreatywni”). Bez Fakera. Formularza, który już
 * istnieje (też w koszu), NIE nadpisuje — zmiany z panelu zostają; listę warsztatów zmienia się w kreatorze formularza.
 * Na stronie: /formularz/zamowienie-warsztatow albo shortcode [formularz:zamowienie-warsztatow].
 */
class KreatywniWorkshopOrderFormSeeder extends Seeder
{
    public const SLUG = 'zamowienie-warsztatow';

    public const UNDECIDED = 'Nie wiem jeszcze — doradźcie';

    public function run(): void
    {
        $site = $this->site();
        if ($site === false) {
            return;
        }

        $existing = FormDefinition::withTrashed()->where('slug', self::SLUG)->first();
        if ($existing) {
            $this->command?->warn('Formularz „'.$existing->title.'” już istnieje (/formularz/'.self::SLUG.') — pominięto, żeby nie nadpisać zmian z panelu.');
            $this->linkOffers($site);

            return;
        }

        $notify = $this->notifyEmail($site);
        FormDefinition::create([
            'title' => 'Zamówienie warsztatów',
            'slug' => self::SLUG,
            'description' => 'Zamów warsztaty kreatywne dla przedszkola, szkoły, firmy albo na urodziny. Odpowiemy z propozycją terminu i wyceną.',
            'is_active' => true,
            'fields' => $this->fields($this->workshops($site)),
            'settings' => [
                'notification_email' => $notify,
                'confirmation_message' => 'Dziękujemy za zamówienie! Odezwiemy się w ciągu 2 dni roboczych, żeby potwierdzić termin i szczegóły warsztatów.',
                'notification_subject' => 'Nowe zamówienie warsztatów',
                'delivery' => 'email',
                'reply_to_submitter' => true,
                'send_copy_to_submitter' => true,
                'submitter_copy_message' => 'Dziękujemy za zamówienie warsztatów. Poniżej kopia przesłanych informacji — odezwiemy się wkrótce.',
            ],
        ]);

        $this->command?->info('Dodano formularz „Zamówienie warsztatów” — /formularz/'.self::SLUG
            .($notify !== '' ? ' (powiadomienia: '.$notify.')' : ' — uzupełnij e-mail powiadomień w panelu (Formularze → Ustawienia).'));
        $this->linkOffers($site);
    }

    /** Witryna „kreatywni”: wskazana przez SITE_SLUG albo pierwsza w tym szablonie; null — brak (lista domyślna); false — zły SITE_SLUG. */
    private function site(): SiteSetting|false|null
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? '')));
        $query = SiteSetting::query()->notTrashed();
        if ($slug !== '') {
            $site = $query->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();
            if (! $site) {
                $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

                return false;
            }

            return $site;
        }

        return $query->where('site_template', 'kreatywni')->orderBy('id')->first();
    }

    /** Nazwy warsztatów do listy wyboru (bez przecinków — w kreatorze opcje rozdziela przecinek), bez powtórzeń. */
    private function workshops(?SiteSetting $site): array
    {
        $names = [];
        if ($site && KreatywniOffer::supported()) {
            $offers = KreatywniOffer::withoutGlobalScopes()->where('site_id', $site->getKey())->where('is_active', true)->orderBy('order')->orderBy('id')->get();
            foreach ($offers as $offer) {
                foreach ($offer->itemGroups() as $items) {
                    foreach ($items as $item) {
                        $names[] = $item['title'];
                    }
                }
            }
        }
        if ($names === []) {
            foreach (KreatywniOffer::parseLines(KreatywniTexts::for($site)->get('offer_items')) as $row) {
                $names[] = $row[0];
            }
        }
        $names = array_map(fn ($n) => trim(preg_replace('/\s*,\s*/u', ' ', (string) $n)), $names);
        $names = array_values(array_unique(array_filter($names, fn ($n) => $n !== '')));
        $names[] = self::UNDECIDED;

        return $names;
    }

    private function notifyEmail(?SiteSetting $site): string
    {
        $email = trim((string) (getenv('NOTIFY_EMAIL') ?: ($_SERVER['NOTIFY_EMAIL'] ?? '')));
        if ($email === '' && $site) {
            $email = trim((string) $site->contact_email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /** Przycisk „Zamów warsztaty” ofert witryny bez własnego adresu prowadzi do formularza. */
    private function linkOffers(?SiteSetting $site): void
    {
        if (! $site || ! KreatywniOffer::supported()) {
            return;
        }
        $linked = KreatywniOffer::withoutGlobalScopes()->where('site_id', $site->getKey())
            ->where(fn ($q) => $q->whereNull('button_url')->orWhere('button_url', ''))
            ->update(['button_url' => '/formularz/'.self::SLUG]);
        if ($linked > 0) {
            $this->command?->info('Przycisk zamówienia w ofertach ('.$linked.') prowadzi do formularza.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function fields(array $workshops): array
    {
        $field = fn (string $uid, string $label, string $type, array $extra = []) => $extra + [
            'uid' => $uid, 'label' => $label, 'type' => $type, 'required' => false,
            'placeholder' => null, 'options' => null, 'help_text' => null,
        ];

        return [
            $field('kwostep1', 'Warsztaty', 'step', ['help_text' => 'Jakie zajęcia, kiedy i dla kogo? Termin potwierdzimy po rozmowie.']),
            $field('kwowork1', 'Warsztat', 'select', ['required' => true, 'options' => implode(', ', $workshops), 'help_text' => 'Opisy warsztatów znajdziesz w ofercie. Nie możesz się zdecydować? Wybierz „'.self::UNDECIDED.'”.']),
            $field('kwodate1', 'Preferowany termin', 'date', ['required' => true]),
            $field('kwotime1', 'Preferowana godzina rozpoczęcia', 'time'),
            $field('kwoalt01', 'Inny możliwy termin', 'text', ['placeholder' => 'np. dowolny wtorek w grudniu przed południem']),
            $field('kwocount', 'Liczba uczestników', 'number', ['required' => true, 'placeholder' => 'np. 20']),
            $field('kwoage01', 'Wiek uczestników', 'text', ['required' => true, 'placeholder' => 'np. 5–6 lat, klasa 3, dorośli']),
            $field('kwoplace', 'Miejsce zajęć', 'radio', ['required' => true, 'options' => 'U Was w placówce lub firmie (dojeżdżamy z materiałami), W naszej pracowni, Jeszcze nie wiem']),
            $field('kwoaddr1', 'Adres zajęć', 'text', ['required' => true, 'placeholder' => 'ulica, numer, miejscowość', 'help_text' => 'Tu przyjedziemy z materiałami.', 'show_if_uid' => 'kwoplace', 'show_if_value' => 'U Was w placówce lub firmie (dojeżdżamy z materiałami)']),

            $field('kwostep2', 'Zamawiający i kontakt', 'step', ['help_text' => 'Komu wysłać potwierdzenie i wycenę?']),
            $field('kwoorg01', 'Placówka, firma lub okazja', 'text', ['placeholder' => 'np. Przedszkole nr 12 w Krakowie, urodziny Zosi']),
            $field('kwoname1', 'Imię i nazwisko', 'text', ['required' => true, 'placeholder' => 'np. Anna Kowalska']),
            $field('kwomail1', 'Adres e-mail', 'email', ['required' => true, 'placeholder' => 'np. anna@przedszkole.pl']),
            $field('kwotel01', 'Numer telefonu', 'tel', ['required' => true, 'placeholder' => 'np. 600 100 200']),
            $field('kwoinv01', 'Faktura', 'checkboxes', ['options' => 'Potrzebuję faktury', 'help_text' => 'Dane do faktury możesz podać w uwagach.']),
            $field('kwonote1', 'Uwagi', 'textarea', ['placeholder' => 'Potrzeby uczestników (np. alergie, dostępność, tłumaczenie na PJM), budżet, pytania']),
            $field('kworodo1', 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu obsługi zamówienia warsztatów i kontaktu w tej sprawie.', 'consent', ['required' => true]),
        ];
    }
}
