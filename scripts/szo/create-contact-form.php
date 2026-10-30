<?php
/**
 * Tworzy w SZO (feerSZO) formularz webowy „Kontakt” (slug: kontakt) dla formularza kontaktowego z feer.org.pl.
 * Dzięki niemu zgłoszenia z /formularz/kontakt trafiają do CRM-a.
 *
 * Skrypt działa tak jak zapis z panelu SZO (CRM → Formularze webowe): wstawia wiersz do `crm_web_forms`.
 * Jest idempotentny: gdy formularz o slugu „kontakt” już istnieje, niczego nie zmienia.
 *
 * Uruchomienie (na serwerze SZO, z katalogu aplikacji):
 *   cd ~/feerSZO && php /ścieżka/do/create-contact-form.php
 *
 * Pola dodatkowe („Do kogo piszesz”, „Temat”) SZO dopisze samo przy pierwszym zgłoszeniu, bo CMS wysyła ich opis
 * razem ze zgłoszeniem. Powiadomienia e-mail o zgłoszeniu wysyła CMS (fundacja@feer.org.pl), więc `notify_email`
 * zostaje puste, żeby wiadomości nie dublowały się.
 */
$root = getcwd();
if (! is_file($root . '/config.php') || ! is_file($root . '/includes/db.php')) {
    fwrite(STDERR, "Uruchom skrypt z katalogu aplikacji SZO (tam, gdzie leży config.php), np. cd ~/feerSZO.\n");
    exit(1);
}

require_once $root . '/config.php';
require_once $root . '/includes/db.php';
require_once $root . '/includes/crm.php';

crm_migrate();

$slug = 'kontakt';

if (db_one('SELECT id FROM crm_web_forms WHERE slug = ?', [$slug])) {
    echo "Formularz „{$slug}” już istnieje w SZO. Nic nie zmieniono.\n";
    exit(0);
}

$fields = [
    ['name' => 'imie_nazwisko', 'required' => true],
    ['name' => 'email', 'required' => true],
    ['name' => 'telefon', 'required' => false],
    ['name' => 'notatka', 'required' => false],
];

$consents = [[
    'id' => 'c1',
    'text' => 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu odpowiedzi na wiadomość.',
    'required' => true,
    'link' => 'https://feer.org.pl/rodo',
    'link_text' => 'Więcej informacji',
    'purpose_id' => null,
]];

// Styl i ustawienia jak domyślne w panelu SZO (crm/form/manage.php).
$style = [
    'accent' => '#0176D3', 'bg' => '#f8fafc', 'max_width' => 520, 'btn_text' => 'Wyślij zgłoszenie',
    'logo_url' => null, 'header_text' => null, 'font' => 'system', 'rounded' => 14,
];

db_insert('crm_web_forms', [
    'slug' => $slug,
    'title' => 'Formularz kontaktowy feer.org.pl',
    'description' => 'Wiadomości z formularza kontaktowego na stronie feer.org.pl.',
    'fields_json' => json_encode($fields, JSON_UNESCAPED_UNICODE),
    'consents_json' => json_encode($consents, JSON_UNESCAPED_UNICODE),
    'style_json' => json_encode($style, JSON_UNESCAPED_UNICODE),
    'automations_json' => json_encode([], JSON_UNESCAPED_UNICODE),
    'group_id' => null,
    'default_status' => 'prospect',
    'success_msg' => 'Dziękujemy! Twoja wiadomość została przyjęta.',
    'notify_email' => null,
    'is_active' => 1,
    'created_by' => null,
    'created_at' => date('Y-m-d H:i:s'),
]);

echo "Utworzono formularz „{$slug}” w SZO. Podepnij go w CMS: php artisan db:seed --class=ContactFormSzoLinkSeeder --force\n";
