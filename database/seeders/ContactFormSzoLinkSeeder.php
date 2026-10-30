<?php

namespace Database\Seeders;

use App\Models\FormDefinition;
use App\Services\SzoClient;
use Illuminate\Database\Seeder;

/**
 * Podpina formularz kontaktowy CMS (slug `kontakt`) do formularza „kontakt” w SZO, żeby zgłoszenia trafiały do CRM-a.
 *
 * Bez podpięcia formularz bez `szo_form_slug` jest świadomie pomijany przy wysyłce do SZO, więc zgłoszenia zostają
 * tylko w CMS-ie i w mailu. Seeder podpina formularz wyłącznie wtedy, gdy SZO faktycznie ma formularz o tym slugu
 * (najpierw uruchom w SZO scripts/szo/create-contact-form.php), i nie nadpisuje istniejącego innego podpięcia.
 *
 * Po podpięciu dosyłanie zaległych zgłoszeń: `php artisan szo:push-submissions` (harmonogram robi to też sam co 15 minut).
 *
 *   php artisan db:seed --class=ContactFormSzoLinkSeeder --force
 */
class ContactFormSzoLinkSeeder extends Seeder
{
    public const SZO_SLUG = 'kontakt';

    public function run(): void
    {
        $form = FormDefinition::withoutGlobalScopes()->where('slug', 'kontakt')->first();

        if (! $form) {
            $this->command?->warn('Pominięto: brak formularza kontaktowego (slug kontakt).');

            return;
        }

        $settings = (array) $form->settings;
        $current = trim((string) ($settings['szo_form_slug'] ?? ''));

        if ($current === self::SZO_SLUG) {
            $this->command?->line('Pominięto: formularz kontaktowy jest już podpięty do SZO.');

            return;
        }

        if ($current !== '') {
            $this->command?->warn("Pominięto: formularz kontaktowy jest podpięty do innego formularza SZO („{$current}”).");

            return;
        }

        $szo = app(SzoClient::class);

        if (! $szo->enabled()) {
            $this->command?->warn('Pominięto: integracja z SZO jest wyłączona (SZO_ENABLED, SZO_URL, SZO_TOKEN).');

            return;
        }

        if (! $szo->form(self::SZO_SLUG)) {
            $this->command?->warn('Pominięto: w SZO nie ma formularza „'.self::SZO_SLUG.'”. Najpierw uruchom w SZO scripts/szo/create-contact-form.php.');

            return;
        }

        $settings['szo_form_slug'] = self::SZO_SLUG;
        $form->settings = $settings;
        $form->save();

        $this->command?->info('Podpięto formularz kontaktowy do formularza „'.self::SZO_SLUG.'” w SZO. Zaległe zgłoszenia: php artisan szo:push-submissions.');
    }
}
