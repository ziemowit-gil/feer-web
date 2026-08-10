<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('pages')->insertOrIgnore([
            'slug'             => 'wspolpraca',
            'title'            => 'Współpraca z FEER',
            'type'             => 'wspolpraca',
            'is_published'     => true,
            'is_system'        => true,
            'show_in_menu'     => false,
            'show_side_nav'    => false,
            'cooperation_data' => json_encode([
                'hero_badge'       => 'Partnerstwo i współpraca',
                'hero_subtitle'    => 'FEER łączy biznes, samorząd, naukę i organizacje pozarządowe wokół wspólnych wartości. Razem działamy skuteczniej, docieramy dalej i tworzymy zmiany, które zostają.',
                'hero_cta1_label'  => 'Zostań partnerem',
                'hero_cta1_url'    => '/kontakt',
                'hero_cta2_label'  => 'Poznaj formy współpracy',
                'sectors_heading'  => 'Dlaczego warto z nami współpracować?',
                'sectors_subtitle' => 'Każdy sektor ma inne potrzeby — mamy na to odpowiedź.',
                'sectors'          => [
                    ['icon' => 'fa-solid fa-building',    'color' => 'blue',   'title' => 'Biznes',               'text' => 'Realizuj strategię CSR i ESG z realnym wpływem społecznym. Angażuj pracowników w wolontariat kompetencyjny, buduj wizerunek odpowiedzialnej marki i docieraj do nowych odbiorców.', 'tag1' => 'CSR / ESG', 'tag2' => 'Wolontariat pracowniczy', 'tag3' => 'Wizerunek marki'],
                    ['icon' => 'fa-solid fa-landmark',    'color' => 'green',  'title' => 'Samorząd i instytucje','text' => 'Wzmacniaj dialog obywatelski i realizuj cele polityki społecznej. Współpracując z FEER, docierasz do aktywnych mieszkańców i budujesz kapitał zaufania w społeczności.', 'tag1' => 'Dialog obywatelski', 'tag2' => 'Polityka społeczna', 'tag3' => 'Aktywizacja lokalna'],
                    ['icon' => 'fa-solid fa-flask',       'color' => 'purple', 'title' => 'Nauka i edukacja',     'text' => 'Przekuwaj badania w realne innowacje społeczne. Studenci zdobywają praktyczne doświadczenie, uczelnie budują relacje ze społeczeństwem, a FEER — nowoczesne narzędzia działania.', 'tag1' => 'Innowacje społeczne', 'tag2' => 'Praktyki i badania', 'tag3' => 'Transfer wiedzy'],
                    ['icon' => 'fa-solid fa-people-group','color' => 'orange', 'title' => 'Inne NGO',             'text' => 'Łączmy siły, by działać skuteczniej. Synergia zasobów, wspólne projekty i koalicje rzecznicze dają organizacjom pozarządowym większą siłę sprawczą niż działanie w pojedynkę.', 'tag1' => 'Koalicje i synergia', 'tag2' => 'Wymiana zasobów', 'tag3' => 'Wspólny advocacy'],
                ],
                'forms_heading'   => 'Formy współpracy',
                'forms_subtitle'  => 'Wybierz formułę dopasowaną do Twoich możliwości i celów.',
                'forms'           => [
                    ['icon' => 'fa-solid fa-star',                  'title' => 'Partnerstwo strategiczne',  'text' => 'Długofalowa współpraca z jasno określonymi celami i wzajemnymi korzyściami dla obu stron.'],
                    ['icon' => 'fa-solid fa-circle-dollar-to-slot', 'title' => 'Sponsoring',               'text' => 'Wsparcie finansowe konkretnych projektów lub wydarzeń z ekspozycją marki i widoczną obecnością.'],
                    ['icon' => 'fa-solid fa-user-gear',             'title' => 'Wolontariat kompetencyjny','text' => 'Eksperci Twojej organizacji dzielą się wiedzą i umiejętnościami — prawo, IT, marketing, HR i inne.'],
                    ['icon' => 'fa-solid fa-sitemap',               'title' => 'Koalicje i sieci',         'text' => 'Wspólne kampanie rzecznicze, projekty wielosektorowe i sieci wymiany doświadczeń.'],
                ],
                'cta_heading'      => 'Zacznijmy rozmowę',
                'cta_text'         => 'Każda trwała współpraca zaczyna się od jednej wiadomości. Napisz do nas — opowiedz, kim jesteś i co chcesz osiągnąć, a my odpiszemy z propozycją kolejnych kroków.',
                'cta_button_label' => 'Napisz do nas',
                'cta_button_url'   => '/kontakt',
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('pages')->insertOrIgnore([
            'slug'         => 'dolacz',
            'title'        => 'Dołącz do nas',
            'type'         => 'links_hub',
            'is_published' => true,
            'is_system'    => true,
            'show_in_menu' => false,
            'show_side_nav'=> false,
            'hub_intro'    => 'Wybierz jak chcesz się zaangażować — każda forma jest cenna.',
            'hub_links'    => json_encode([
                ['icon' => 'fa-solid fa-hands-helping', 'color' => 'blue',  'label' => 'Wolontariat', 'url' => '/wolontariat', 'description' => 'Działaj z nami jako wolontariusz — angażuj czas i umiejętności.', 'cta_label' => 'Dowiedz się więcej o wolontariacie'],
                ['icon' => 'fa-solid fa-briefcase',     'color' => 'dark',  'label' => 'Praca',       'url' => '/praca',       'description' => 'Dołącz do zespołu FEER — sprawdź aktualne oferty zatrudnienia.', 'cta_label' => 'Dowiedz się więcej o pracy'],
                ['icon' => 'fa-solid fa-handshake',     'color' => 'green', 'label' => 'Współpraca',  'url' => '/wspolpraca',  'description' => 'Zostań partnerem — biznes, samorząd, nauka lub NGO.', 'cta_label' => 'Dowiedz się więcej o współpracy'],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->whereIn('slug', ['wspolpraca', 'dolacz'])->where('is_system', true)->delete();
    }
};
