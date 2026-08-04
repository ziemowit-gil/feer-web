<?php

namespace Database\Seeders;

use App\Models\MailTemplate;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'      => 'contact-message',
                'name'      => 'Wiadomość z formularza kontaktowego',
                'subject'   => 'Wiadomość ze strony od {{sender_name}}',
                'body'      => '<p>Nowa wiadomość z formularza kontaktowego na stronie.</p>
<p><strong>Od:</strong> {{sender_name}}<br><strong>E-mail:</strong> {{sender_email}}</p>
<p><strong>Treść wiadomości:</strong></p>
<p>{{message}}</p>',
                'variables' => [
                    'sender_name'  => 'Imię i nazwisko nadawcy',
                    'sender_email' => 'Adres e-mail nadawcy',
                    'message'      => 'Treść wiadomości',
                ],
            ],
            [
                'slug'      => 'meeting-signup',
                'name'      => 'Zgłoszenie na spotkanie (powiadomienie admina)',
                'subject'   => 'Nowe zgłoszenie „Daj znać, że przyjdziesz" od {{sender_name}}',
                'body'      => '<p>Ktoś dał znać, że wybiera się na spotkanie (formularz „Daj znać, że przyjdziesz" na stronie).</p>
<p><strong>Imię i nazwisko:</strong> {{sender_name}}<br><strong>E-mail:</strong> {{sender_email}}{{term_line}}</p>
{{message_section}}',
                'variables' => [
                    'sender_name'      => 'Imię i nazwisko',
                    'sender_email'     => 'Adres e-mail',
                    'term_line'        => 'Wiersz z terminem (wypełniany automatycznie gdy podano)',
                    'message_section'  => 'Sekcja z wiadomością (wypełniana automatycznie gdy podano)',
                ],
            ],
            [
                'slug'      => 'meeting-confirmation',
                'name'      => 'Potwierdzenie spotkania (do uczestnika)',
                'subject'   => 'Potwierdzenie Twojego zgłoszenia — {{site_name}}',
                'body'      => '<p>Cześć <strong>{{name}}</strong>,</p>
<p>potwierdzamy Twoje zgłoszenie na spotkanie z {{site_name}}.</p>
{{term_section}}
<p>Do zobaczenia! Jeśli masz pytania, skontaktuj się z nami: <a href="mailto:{{contact_email}}">{{contact_email}}</a></p>
<p>Pozdrawiamy,<br>Zespół {{site_name}}</p>',
                'variables' => [
                    'name'           => 'Imię uczestnika',
                    'site_name'      => 'Nazwa organizacji',
                    'contact_email'  => 'Adres e-mail kontaktowy',
                    'term_section'   => 'Sekcja z terminem (wypełniana automatycznie gdy podano)',
                ],
            ],
            [
                'slug'      => 'accessibility-report',
                'name'      => 'Zgłoszenie bariery dostępności',
                'subject'   => 'Nowe zgłoszenie bariery dostępności',
                'body'      => '<p>Ktoś zgłosił problem z dostępnością przez formularz na stronie deklaracji dostępności.</p>
<p>{{name_line}}<strong>E-mail (do odpowiedzi):</strong> {{sender_email}}{{page_url_line}}</p>
<p><strong>Opis bariery:</strong></p>
<p>{{message}}</p>
<p style="color:#666;font-size:12px">Zgodnie z ustawą o dostępności cyfrowej na zgłoszenie należy odpowiedzieć bez zbędnej zwłoki, najpóźniej w ciągu 7 dni.</p>',
                'variables' => [
                    'name_line'      => 'Wiersz z imieniem (wypełniany automatycznie gdy podano)',
                    'sender_email'   => 'Adres e-mail',
                    'page_url_line'  => 'Wiersz z adresem strony (wypełniany automatycznie gdy podano)',
                    'message'        => 'Opis bariery',
                ],
            ],
            [
                'slug'      => 'schedule-change',
                'name'      => 'Zmiana harmonogramu spotkań',
                'subject'   => 'Zmiana harmonogramu spotkań — {{site_name}}',
                'body'      => '<p>Cześć,</p>
<p>informujemy, że zmienił się nasz harmonogram spotkań stacjonarnych ({{schedule_title}}). Aktualne terminy:</p>
{{schedule_items}}
<p>Do zobaczenia!<br>Zespół {{site_name}}</p>
<p style="color:#888;font-size:12px;">Otrzymujesz tę wiadomość, bo zgłosiłeś(-aś) chęć udziału w spotkaniu przez formularz „Daj znać, że przyjdziesz".</p>',
                'variables' => [
                    'site_name'      => 'Nazwa organizacji',
                    'schedule_title' => 'Tytuł harmonogramu',
                    'schedule_items' => 'Lista terminów (generowana automatycznie)',
                ],
            ],
        ];

        foreach ($templates as $data) {
            MailTemplate::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
