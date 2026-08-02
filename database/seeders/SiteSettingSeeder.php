<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::updateOrCreate(['id' => 1], [
            // Podstawowe
            'site_name'          => 'Fundacja FEER',
            'tagline'            => 'Dostępność cyfrowa dla wszystkich',
            'brand_color'        => '#c31432',
            'brand_color_2'      => '#8b0d20',
            'meta_description'   => 'Fundacja FEER – audyty WCAG, szkolenia z dostępności cyfrowej i platforma vLAB dla szkół i organizacji.',
            'allow_indexing'     => false,
            'content_editor'     => 'quill',

            // Kontakt
            'contact_address'          => 'ul. Barbackiego 28',
            'contact_city'             => '33-300 Nowy Sącz',
            'contact_email'            => 'kontakt@feer.org.pl',
            'contact_phone'            => '+48 18 123 45 67',
            'contact_intro'            => 'Masz pytania? Skontaktuj się z nami – odpiszemy w ciągu jednego dnia roboczego.',
            'contact_show_form'        => true,
            'contact_show_bank_accounts' => true,
            'contact_schedule_enabled' => true,
            'contact_schedule'         => [
                ['days' => 'Poniedziałek – Piątek', 'hours' => '9:00 – 17:00'],
                ['days' => 'Sobota',                'hours' => 'nieczynne'],
            ],

            // Social media
            'facebook_url'  => 'https://www.facebook.com/fundacjafeer',
            'youtube_url'   => 'https://www.youtube.com/@fundacjafeer',
            'linkedin_url'  => 'https://www.linkedin.com/company/fundacja-feer',
            'show_topbar_social' => true,
            'show_topbar_bip'    => true,

            // Rejestr
            'krs_number'   => '0001234567',
            'nip_number'   => '734-000-00-01',
            'regon_number' => '380000001',

            // Konto bankowe
            'bank_account_number'     => 'PL 12 1234 5678 9012 3456 7890 1234',
            'bank_account_tax_number' => 'PL 12 1234 5678 9012 3456 7890 1235',
            'contact_show_bank_accounts' => true,

            // BIP
            'bip_mode'         => 'internal',
            'bip_intro'        => 'Biuletyn Informacji Publicznej Fundacji FEER prowadzony jest zgodnie z ustawą z dnia 6 września 2001 r. o dostępie do informacji publicznej.',
            'bip_editor_name'  => 'Administrator Demo',
            'bip_editor_email' => 'admin@demo.feer.org.pl',
            'bip_gov_url'      => 'https://www.gov.pl/web/bip',

            // Dostępność
            'accessibility_entity_name'       => 'Fundacja FEER',
            'accessibility_status'            => 'partial',
            'accessibility_status_note'       => 'Serwis jest częściowo zgodny z wymaganiami ustawy o dostępności cyfrowej. Trwają prace nad uzupełnieniem brakujących elementów.',
            'accessibility_declaration_date'  => '2025-01-15',
            'accessibility_review_method'     => 'self',
            'accessibility_contact_name'      => 'Administrator',
            'accessibility_contact_email'     => 'dostepnosc@feer.org.pl',

            // Wsparcie
            'support_intro'           => 'Twoje wsparcie pozwala nam rozwijać otwarte narzędzia dostępności i szkolić kolejnych specjalistów.',
            'support_hero_title'      => 'Wesprzyj dostępność cyfrową',
            'support_hero_subtitle'   => 'Każda złotówka trafia bezpośrednio w działania fundacji.',
            'support_hero_cta_label'  => 'Przekaż darowiznę',
            'support_methods_title'   => 'Jak możesz nam pomóc?',
            'support_show_partners'   => true,

            // Układ
            'header_layout'           => 'default',
            'news_layout'             => 'grid',
            'volunteer_layout'        => 'list',
            'show_cms_credit'         => true,
        ]);
    }
}
