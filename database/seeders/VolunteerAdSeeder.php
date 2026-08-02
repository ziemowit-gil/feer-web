<?php

namespace Database\Seeders;

use App\Models\VolunteerAd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VolunteerAdSeeder extends Seeder
{
    public function run(): void
    {
        $ads = [
            [
                'slug'              => 'tester-dostepnosci',
                'title'             => 'Wolontariusz/ka – tester/ka dostępności stron',
                'lead'              => 'Pomagasz nam sprawdzić, czy strony internetowe są przyjazne dla wszystkich użytkowników – szczególnie osób z niepełnosprawnościami.',
                'q_beneficiaries'   => 'Osoby z niepełnosprawnościami i wszyscy użytkownicy sieci, którym zależy na dostępnych serwisach.',
                'q_tasks'           => [
                    'Testowanie stron internetowych pod kątem zgodności z WCAG 2.2',
                    'Wypełnianie arkuszy audytu dostępności',
                    'Zgłaszanie błędów i proponowanie poprawek',
                    'Udział w spotkaniach podsumowujących wyniki testów',
                ],
                'q_mode'            => 'zdalnie',
                'q_schedule'        => 'Elastycznie, średnio 3–5 godzin tygodniowo',
                'q_time_commitment' => '3–5 godz./tydzień',
                'q_benefits'        => [
                    'Zaświadczenie wolontariusza z opisem zrealizowanych zadań',
                    'Dostęp do szkoleń i materiałów z zakresu dostępności cyfrowej',
                    'Wpis do bazy wolontariuszy fundacji',
                    'Realne doświadczenie do CV',
                ],
                'q_how_to_apply'    => 'Wyślij krótki e-mail na wolontariat@feer.org.pl z informacją o sobie i motywacji. Odpiszemy w ciągu 3 dni roboczych.',
                'contact_name'      => 'Koordynatorka wolontariatu',
                'contact_email'     => 'wolontariat@feer.org.pl',
                'audience'          => 'brand',
                'is_published'      => true,
                'order'             => 1,
            ],
            [
                'slug'              => 'grafik-social-media',
                'title'             => 'Wolontariusz/ka – grafik/czka do social mediów',
                'lead'              => 'Tworzysz grafiki i materiały wizualne promujące działania fundacji w mediach społecznościowych.',
                'q_beneficiaries'   => 'Fundacja FEER i społeczność zainteresowana tematyką dostępności cyfrowej.',
                'q_tasks'           => [
                    'Tworzenie grafik do postów na Facebooku, Instagramie i LinkedIn w narzędziu Canva lub Adobe',
                    'Przygotowywanie szablonów graficznych dla materiałów cyklicznych',
                    'Dostosowywanie grafik do wymagań dostępności (kontrast, alt text)',
                    'Miesięczna współpraca z koordynatorką komunikacji',
                ],
                'q_mode'            => 'zdalnie',
                'q_schedule'        => '2–4 godziny tygodniowo, możliwość ustalenia własnego rytmu pracy',
                'q_time_commitment' => '2–4 godz./tydzień',
                'q_benefits'        => [
                    'Zaświadczenie wolontariusza',
                    'Portfolio zrealizowanych materiałów',
                    'Mentoring i feedback od specjalistki ds. komunikacji',
                ],
                'q_how_to_apply'    => 'Prześlij przykłady swoich prac (portfolio lub link) na adres wolontariat@feer.org.pl z dopiskiem „Grafik wolontariat".',
                'contact_name'      => 'Marta Nowak',
                'contact_email'     => 'wolontariat@feer.org.pl',
                'application_cta_label' => 'Aplikuj',
                'audience'          => 'ngo',
                'is_published'      => true,
                'order'             => 2,
            ],
            [
                'slug'              => 'wsparcie-szkolen',
                'title'             => 'Wolontariusz/ka – wsparcie organizacji szkoleń',
                'lead'              => 'Pomagasz nam przygotować i przeprowadzić szkolenia stacjonarne i online dla uczestników z całej Polski.',
                'q_beneficiaries'   => 'Uczestnicy szkoleń fundacji: pracownicy NGO, samorządów i szkół.',
                'q_tasks'           => [
                    'Wsparcie rejestracji uczestników i obsługi systemu zapisów',
                    'Pomoc w przygotowaniu sali i materiałów szkoleniowych',
                    'Koordynacja techniczna webinarów (obsługa platformy online)',
                    'Zbieranie ankiet ewaluacyjnych po szkoleniu',
                ],
                'q_mode'            => 'hybrydowo',
                'q_location'        => 'Nowy Sącz (szkolenia stacjonarne) + zdalnie (webinary)',
                'q_schedule'        => 'W zależności od terminarza szkoleń, zwykle weekendy lub popołudnia',
                'q_time_commitment' => 'Średnio 1 szkolenie miesięcznie (4–8 godz.)',
                'q_benefits'        => [
                    'Bezpłatny udział w szkoleniu jako uczestnik',
                    'Zaświadczenie wolontariusza',
                    'Sieć kontaktów ze środowiska NGO',
                ],
                'q_how_to_apply'    => 'Zgłoś się przez formularz kontaktowy na stronie lub napisz na wolontariat@feer.org.pl.',
                'contact_name'      => 'Koordynatorka projektów',
                'contact_email'     => 'wolontariat@feer.org.pl',
                'audience'          => 'brand',
                'is_published'      => true,
                'closes_at'         => now()->addDays(45)->format('Y-m-d'),
                'order'             => 3,
            ],
        ];

        foreach ($ads as $data) {
            VolunteerAd::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
