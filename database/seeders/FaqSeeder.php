<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            // Dostępność cyfrowa
            [
                'question'     => 'Czym jest WCAG i dlaczego jest ważne?',
                'answer'       => 'WCAG (Web Content Accessibility Guidelines) to zestaw wytycznych opracowanych przez organizację W3C, określających zasady tworzenia dostępnych treści internetowych. Dostępność cyfrowa jest wymagana ustawowo od podmiotów publicznych, ale wdrożenie WCAG przynosi korzyści wszystkim użytkownikom – osobom starszym, z niepełnosprawnościami, a także użytkownikom urządzeń mobilnych.',
                'category'     => 'Dostępność cyfrowa',
                'is_published' => true,
                'order'        => 1,
            ],
            [
                'question'     => 'Jak zamówić audyt WCAG dla swojej strony internetowej?',
                'answer'       => 'Aby zamówić audyt dostępności, prosimy o kontakt mailowy na adres audyt@feer.org.pl lub przez formularz kontaktowy na naszej stronie. W ciągu 2 dni roboczych skontaktujemy się w celu omówienia zakresu i harmonogramu badania.',
                'category'     => 'Dostępność cyfrowa',
                'is_published' => true,
                'order'        => 2,
            ],
            [
                'question'     => 'Jak zgłosić barierę dostępności na stronie fundacji?',
                'answer'       => 'Bariery dostępności na stronie fundacji można zgłaszać mailowo na adres dostepnosc@feer.org.pl lub telefonicznie pod numer +48 18 123 45 67. Każde zgłoszenie rozpatrujemy w ciągu 7 dni roboczych i informujemy o sposobie rozwiązania problemu.',
                'category'     => 'Dostępność cyfrowa',
                'is_published' => true,
                'order'        => 3,
            ],
            [
                'question'     => 'Czy audyt WCAG jest obowiązkowy dla organizacji pozarządowych?',
                'answer'       => 'Ustawa o dostępności cyfrowej z 2019 r. obowiązuje przede wszystkim podmioty publiczne. Jednak organizacje pozarządowe, które realizują zadania publiczne lub korzystają z dofinansowania publicznego, coraz częściej muszą spełniać wymogi dostępności. Rekomendujemy wdrożenie WCAG niezależnie od obowiązku prawnego – to korzyść dla wszystkich użytkowników.',
                'category'     => 'Dostępność cyfrowa',
                'is_published' => true,
                'order'        => 4,
            ],

            // Platforma vLAB
            [
                'question'     => 'Czym jest platforma vLAB i kto może z niej korzystać?',
                'answer'       => 'vLAB to platforma wirtualnych laboratoriów szkoleniowych uruchamianych w chmurze, bez potrzeby instalacji oprogramowania. Przeznaczona jest dla szkół, uczelni i organizacji prowadzących szkolenia techniczne. Dostęp wymaga rejestracji instytucji i jest bezpłatny dla placówek edukacyjnych w ramach programów partnerskich.',
                'category'     => 'Platforma vLAB',
                'is_published' => true,
                'order'        => 1,
            ],
            [
                'question'     => 'Jak zgłosić szkołę lub organizację do programu vLAB?',
                'answer'       => 'Formularz zgłoszeniowy dostępny jest na stronie projektu vLAB. Po weryfikacji zgłoszenia kontaktujemy się z opiekunem instytucji w celu podpisania umowy partnerskiej i uruchomienia dostępu. Cały proces trwa zwykle od 3 do 5 dni roboczych.',
                'category'     => 'Platforma vLAB',
                'is_published' => true,
                'order'        => 2,
            ],

            // Szkolenia
            [
                'question'     => 'Czy szkolenia fundacji są bezpłatne?',
                'answer'       => 'Część szkoleń organizujemy bezpłatnie dzięki wsparciu projektowemu i grantowemu. Szkolenia komercyjne i zamawiane przez firmy są odpłatne – cennik dostępny jest po zapytaniu ofertowym. Aktualne szkolenia bezpłatne i płatne znajdziesz w dziale Szkolenia.',
                'category'     => 'Szkolenia',
                'is_published' => true,
                'order'        => 1,
            ],
            [
                'question'     => 'Czy po ukończeniu szkolenia otrzymam zaświadczenie?',
                'answer'       => 'Tak, uczestnicy szkoleń stacjonarnych i webinarów organizowanych przez Fundację FEER otrzymują zaświadczenie uczestnictwa. Wzór zaświadczenia zawiera informację o zakresie tematycznym i liczbie godzin szkolenia.',
                'category'     => 'Szkolenia',
                'is_published' => true,
                'order'        => 2,
            ],

            // Wolontariat
            [
                'question'     => 'Jak zostać wolontariuszem w Fundacji FEER?',
                'answer'       => 'Aktualne ogłoszenia wolontariackie publikujemy w zakładce Wolontariat. Każde ogłoszenie zawiera opis zadań, wymagania i sposób zgłoszenia. Jeśli nie widzisz ogłoszenia pasującego do Twoich kompetencji, napisz do nas na kontakt@feer.org.pl – zbieramy zgłoszenia do wolontariatu stałego.',
                'category'     => 'Wolontariat',
                'is_published' => true,
                'order'        => 1,
            ],

            // Wsparcie finansowe
            [
                'question'     => 'Jak przekazać darowiznę na rzecz Fundacji?',
                'answer'       => 'Darowizny można przekazywać przelewem bankowym na konto: PL 12 1234 5678 9012 3456 7890 1234. W tytule prosimy wpisać „Darowizna". Fundacja wystawia potwierdzenie odbioru darowizny – prosimy o kontakt mailowy po wykonaniu przelewu. Możliwy jest też przekaz 1,5% podatku – nasz numer KRS to 0001234567.',
                'category'     => 'Wsparcie finansowe',
                'is_published' => true,
                'order'        => 1,
            ],
        ];

        foreach ($faqs as $data) {
            Faq::updateOrCreate(
                ['question' => $data['question']],
                $data,
            );
        }
    }
}
