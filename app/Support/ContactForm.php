<?php

namespace App\Support;

use App\Models\FormDefinition;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Formularz kontaktowy w kreatorze formularzy (Formularze → „Kontakt”, slug `kontakt`).
 *
 * Tworzy się sam przy pierwszym wyświetleniu strony kontaktu — z tymi samymi polami co dawny
 * formularz zapisany w kodzie — a potem redaktor zmienia pola, zgodę i powiadomienia w kreatorze.
 * Zgłoszenia trafiają do Formularze → Kontakt → Zgłoszenia (i do SZO, gdy formularz jest podpięty).
 *
 * Null (dawny formularz w kodzie), gdy moduł formularzy jest wyłączony albo redaktor usunął „Kontakt”.
 */
class ContactForm
{
    public const SLUG = 'kontakt';

    public static function definition(): ?FormDefinition
    {
        if (! SiteSetting::current()->isModuleEnabled('forms') || ! Route::has('formularz.store')) {
            return null;
        }

        $form = FormDefinition::withTrashed()->where('slug', self::SLUG)->first();
        if ($form?->trashed()) {
            return null;
        }

        return $form ?? FormDefinition::create(self::defaults());
    }

    /** Pola i ustawienia dawnego formularza kontaktowego. */
    public static function defaults(): array
    {
        $field = fn (string $label, string $type, bool $required = false, ?string $help = null, ?string $placeholder = null) => [
            'label' => $label, 'type' => $type, 'required' => $required, 'help_text' => $help, 'placeholder' => $placeholder,
            'options' => null, 'uid' => Str::lower(Str::random(8)),
        ];

        return [
            'title' => 'Formularz kontaktowy',
            'slug' => self::SLUG,
            'description' => null,
            'is_active' => true,
            'fields' => [
                $field('Imię i nazwisko', 'text', true),
                $field('E-mail', 'email', true),
                $field('Telefon', 'tel'),
                $field('Do kogo piszesz', 'coordinator', false, 'Wybierz osobę, jeśli piszesz w sprawie konkretnego działania. Bez wyboru wiadomość trafi na ogólny adres fundacji.'),
                $field('Temat', 'text'),
                $field('Wiadomość', 'textarea', true),
                $field('Wyrażam zgodę na przetwarzanie moich danych osobowych w celu odpowiedzi na wiadomość.', 'consent', true),
            ],
            'settings' => [
                'confirmation_message' => 'Wiadomość została wysłana. Odpowiemy najszybciej, jak to możliwe.',
                'submit_label' => 'Wyślij wiadomość',
                'notification_email' => SiteSetting::current()->contact_email,
                'notification_subject' => 'Nowa wiadomość z formularza kontaktowego',
                'reply_to_submitter' => true,
                'delivery' => 'email',
            ],
        ];
    }
}
