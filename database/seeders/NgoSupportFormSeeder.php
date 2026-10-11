<?php

namespace Database\Seeders;

use App\Models\FormDefinition;
use App\Models\NgoSupportRequest;
use Illuminate\Database\Seeder;

/**
 * Formularz „Wsparcie technologiczne dla NGO” w module Formularze (kreator w panelu).
 * Pola odpowiadają kolumnom ngo_support_requests (imię, organizacja, rola, kontakt, obszary
 * wsparcia, opis, preferowany kanał i termin, zgoda RODO). Po wpisaniu numeru KRS nazwa
 * i strona organizacji uzupełniają się z rejestru.
 *
 * Uruchom: php artisan db:seed --class=NgoSupportFormSeeder (na serwerze: php84 artisan …).
 * Bez Fakera (działa na produkcji). Jeśli formularz o tym adresie już istnieje, NIE jest
 * nadpisywany — zmiany zrobione w panelu zostają.
 * Na stronie: /formularz/wsparcie-technologiczne-ngo albo shortcode [formularz:wsparcie-technologiczne-ngo].
 */
class NgoSupportFormSeeder extends Seeder
{
    public const SLUG = 'wsparcie-technologiczne-ngo';

    public function run(): void
    {
        $existing = FormDefinition::withTrashed()->where('slug', self::SLUG)->first();
        if ($existing) {
            $this->command?->warn('Formularz „'.$existing->title.'” już istnieje (/formularz/'.self::SLUG.') — pominięto, żeby nie nadpisać zmian z panelu.');

            return;
        }

        FormDefinition::create([
            'title' => 'Wsparcie technologiczne dla NGO',
            'slug' => self::SLUG,
            'description' => 'Zgłoszenie do odpłatnej działalności pożytku publicznego: wsparcie technologiczne organizacji pozarządowych.',
            'is_active' => true,
            'fields' => $this->fields(),
            'settings' => [
                'confirmation_message' => 'Dziękujemy! Otrzymaliśmy zgłoszenie — odezwiemy się w ciągu 3 dni roboczych wybranym kanałem kontaktu.',
                'notification_subject' => 'Nowe zgłoszenie: wsparcie technologiczne dla NGO',
                'delivery' => 'email',
                'reply_to_submitter' => true,
                'send_copy_to_submitter' => true,
                'submitter_copy_message' => 'Dziękujemy za zgłoszenie. Poniżej kopia przesłanych informacji.',
            ],
        ]);

        $this->command?->info('Dodano formularz „Wsparcie technologiczne dla NGO” — /formularz/'.self::SLUG);
    }

    /** @return array<int, array<string, mixed>> */
    private function fields(): array
    {
        $field = fn (string $uid, string $label, string $type, array $extra = []) => $extra + [
            'uid' => $uid, 'label' => $label, 'type' => $type, 'required' => false,
            'placeholder' => null, 'options' => null, 'help_text' => null,
        ];

        return [
            $field('ngohead1', 'Dane kontaktowe i organizacja', 'heading', ['help_text' => 'Powiedz nam, kim jesteś i jaką organizację reprezentujesz.']),
            $field('ngoname1', 'Imię i nazwisko', 'text', ['required' => true, 'placeholder' => 'np. Anna Kowalska']),
            $field('ngokrs01', 'Numer KRS organizacji', 'krs', ['help_text' => 'Opcjonalnie — po wpisaniu uzupełnimy nazwę i stronę organizacji z rejestru.']),
            $field('ngoorg01', 'Nazwa organizacji NGO', 'text', ['required' => true, 'placeholder' => 'np. Fundacja Otwarte Drzwi', 'krs_fill' => 'nazwa']),
            $field('ngorole1', 'Twoja rola w organizacji', 'select', ['required' => true, 'options' => implode(', ', NgoSupportRequest::ROLES)]),
            $field('ngomail1', 'Adres e-mail', 'email', ['required' => true, 'placeholder' => 'np. anna@fundacja.org.pl']),
            $field('ngotel01', 'Numer telefonu', 'tel', ['help_text' => 'Opcjonalnie — przyda się, jeśli wolisz rozmowę telefoniczną.']),
            $field('ngowww01', 'Strona WWW lub profil w mediach społecznościowych', 'text', ['placeholder' => 'np. https://fundacja.org.pl', 'krs_fill' => 'www']),

            $field('ngohead2', 'Zakres wsparcia i potrzeby', 'heading'),
            $field('ngoarea1', 'W czym możemy pomóc?', 'checkboxes', ['required' => true, 'options' => implode(', ', NgoSupportRequest::SUPPORT_AREAS), 'help_text' => 'Zaznacz wszystkie obszary, których dotyczy zgłoszenie.']),
            $field('ngodesc1', 'Opisz wyzwanie techniczne', 'textarea', ['required' => true, 'placeholder' => 'Co nie działa albo co chcecie zmienić? Ile osób korzysta z systemów? Czy jest termin?']),

            $field('ngohead3', 'Preferencje kontaktu', 'heading'),
            $field('ngochan1', 'Preferowany sposób kontaktu', 'radio', ['required' => true, 'options' => implode(', ', NgoSupportRequest::CONTACT_CHANNELS)]),
            $field('ngotime1', 'Preferowany termin lub godziny rozmowy', 'text', ['placeholder' => 'np. wtorki i czwartki po 15:00']),
            $field('ngorodo1', 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu obsługi zgłoszenia i kontaktu w sprawie wsparcia technologicznego.', 'consent', ['required' => true]),
        ];
    }
}
