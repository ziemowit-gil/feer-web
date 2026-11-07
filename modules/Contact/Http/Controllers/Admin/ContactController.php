<?php

declare(strict_types=1);

namespace Modules\Contact\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use App\Support\CleanTalkGuard;
use App\Support\ContactForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Contact\Support\ContactOptions;
use Modules\Contact\Support\ContactSections;

/**
 * Komunikacja → Kontakt: jedno miejsce modułu Kontakt — stan strony kontaktu, formularz kontaktowy (najczęściej
 * zmieniane ustawienia formularza z kreatora: adres powiadomień, potwierdzenie, kopia dla nadawcy), podstrony w menu
 * bocznym, „Gdzie działamy”, dojazd i dostępność biura, komunikat o zmianie godzin i wizytówka vCard.
 *
 * Dane kontaktowe (adresy, telefon, rachunki, spotkania…) zostają w Ustawienia → Kontakt i w edycji strony „Kontakt”.
 */
final class ContactController extends Controller
{
    public function index(): View
    {
        $settings = SiteSetting::current();
        $form = ContactForm::definition();

        return view('contact-module::admin.index', [
            'settings' => $settings,
            'options' => ContactOptions::for($settings)->toArray(),
            'optionsColumn' => Schema::hasColumn('site_settings', 'contact_options'),
            'contactPage' => Page::forCurrentSite()->where('type', 'contact')->orderBy('id')->first(),
            'sectionLabels' => ContactSections::labels($settings),
            'sectionsWithData' => ContactSections::withData($settings),
            'form' => $form,
            'formUnread' => $form ? $form->unreadSubmissions()->count() : 0,
            'legacyUnread' => Schema::hasTable('contact_messages') ? ContactMessage::whereNull('read_at')->count() : 0,
            'cleanTalk' => CleanTalkGuard::enabled(),
            'bookCount' => Schema::hasTable('phone_book_entries') ? PhoneBookEntry::forCurrentSite()->count() : 0,
            'bookPage' => Page::forCurrentSite()->where('type', 'contact_book')->orderBy('id')->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $emails = function (string $attribute, mixed $value, \Closure $fail): void {
            foreach (array_filter(array_map('trim', explode(',', (string) $value))) as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail("Adres „{$email}” nie wygląda na poprawny e-mail. Kilka adresów oddziel przecinkami.");
                }
            }
        };

        $data = $request->validate([
            'options' => ['array'],
            'options.form_enabled' => ['nullable', 'boolean'],
            'options.response_time' => ['nullable', 'string', 'max:200'],
            'options.shown_sections' => ['nullable', 'array'],
            'options.shown_sections.*' => [Rule::in(ContactSections::SLUGS)],
            'options.places_title' => ['nullable', 'string', 'max:80'],
            'options.places_intro' => ['nullable', 'string', 'max:1000'],
            'options.places' => ['nullable', 'array', 'max:'.ContactOptions::MAX_PLACES],
            'options.places.*.name' => ['nullable', 'string', 'max:160'],
            'options.places.*.area' => ['nullable', 'string', 'max:160'],
            'options.places.*.description' => ['nullable', 'string', 'max:1500'],
            'options.places.*.address' => ['nullable', 'string', 'max:255'],
            'options.places.*.url' => ['nullable', 'string', 'max:500', 'regex:~^(https?://\S+|/(?!/)\S*)$~i'],
            'options.places.*.url_label' => ['nullable', 'string', 'max:80'],
            'options.directions' => ['nullable', 'string', 'max:2000'],
            'options.accessibility' => ['nullable', 'string', 'max:2000'],
            'options.map_link' => ['nullable', 'boolean'],
            'options.closure_text' => ['nullable', 'string', 'max:500'],
            'options.closure_from' => ['nullable', 'date'],
            'options.closure_until' => ['nullable', 'date', 'after_or_equal:options.closure_from'],
            'options.vcard' => ['nullable', 'boolean'],
            'form' => ['nullable', 'array'],
            'form.is_active' => ['nullable', 'boolean'],
            'form.notification_email' => ['nullable', 'string', 'max:500', $emails],
            'form.notification_cc' => ['nullable', 'string', 'max:500', $emails],
            'form.notification_subject' => ['nullable', 'string', 'max:200'],
            'form.confirmation_message' => ['nullable', 'string', 'max:1000'],
            'form.submit_label' => ['nullable', 'string', 'max:60'],
            'form.reply_to_submitter' => ['nullable', 'boolean'],
            'form.send_copy_to_submitter' => ['nullable', 'boolean'],
            'form.submitter_copy_message' => ['nullable', 'string', 'max:1000'],
        ], [
            'options.closure_until.after_or_equal' => 'Data końca komunikatu nie może być wcześniejsza niż data początku.',
            'options.places.*.url.regex' => 'Link przy miejscu musi zaczynać się od https:// albo od / (adres w serwisie).',
            'options.places.max' => 'Możesz dodać najwyżej '.ContactOptions::MAX_PLACES.' miejsc.',
        ]);

        $notes = [];
        $settings = SiteSetting::current();

        // 1. Opcje modułu (jedno pole JSON). Podstrony: zaznaczone = w menu, reszta ukryta.
        if (Schema::hasColumn('site_settings', 'contact_options')) {
            $in = $data['options'] ?? [];
            $in['form_enabled'] = $request->boolean('options.form_enabled');
            $in['map_link'] = $request->boolean('options.map_link');
            $in['vcard'] = $request->boolean('options.vcard');
            $in['response_time'] = (string) ($in['response_time'] ?? '');
            $in['hidden_sections'] = array_values(array_diff(ContactSections::SLUGS, (array) ($in['shown_sections'] ?? [])));
            unset($in['shown_sections']);

            $settings->forceFill(['contact_options' => ContactOptions::normalize($in)])->save();
        } else {
            $notes[] = 'Opcje modułu zapiszą się po migracji bazy (php artisan migrate).';
        }

        // 2. Formularz kontaktowy z kreatora: te same ustawienia, które są w Formularze → Kontakt.
        if (($form = ContactForm::definition()) instanceof FormDefinition && $request->has('form')) {
            $f = $data['form'] ?? [];
            $form->is_active = $request->boolean('form.is_active');
            $form->settings = array_merge($form->settings ?? [], [
                'notification_email' => trim((string) ($f['notification_email'] ?? '')),
                'notification_cc' => trim((string) ($f['notification_cc'] ?? '')),
                'notification_subject' => $f['notification_subject'] ?? null,
                'confirmation_message' => $f['confirmation_message'] ?? null,
                'submit_label' => trim((string) ($f['submit_label'] ?? '')) ?: null,
                'reply_to_submitter' => $request->boolean('form.reply_to_submitter'),
                'send_copy_to_submitter' => $request->boolean('form.send_copy_to_submitter'),
                'submitter_copy_message' => $f['submitter_copy_message'] ?? null,
            ]);
            $form->save();
        }

        return redirect()->route('admin.kontakt.index')
            ->with('status', trim('Zapisano opcje kontaktu. '.implode(' ', $notes)));
    }
}
