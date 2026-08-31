<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dane demonstracyjne dla instancji szablonu „federation" (federacja organizacji
 * pozarządowych), wzorowane na treściach ze zrzutów ekranu KraFOS.
 */
class FederationDemoSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::updateOrCreate(['id' => 1], [
            'site_name'        => 'Krakowskie Forum Organizacji Społecznych',
            'tagline'          => 'Razem dla lepszego jutra',
            'site_template'    => 'federation',
            'logo_only'        => true,
            'logo_alt'         => 'Krakowskie Forum Organizacji Społecznych — logo',
            'brand_color'      => '#80BA34',
            'brand_color_2'    => '#2EA6DE',
            'brand_color_3'    => '#FFB327',
            'brand_color_4'    => '#CE5740',
            // Kolory pochodzą z realnej palety logo KraFOS — nie przyciemniaj ich
            // automatycznie pod kątem kontrastu przy zapisie w panelu.
            'brand_skip_contrast' => true,
            'meta_description' => 'Krakowskie Forum Organizacji Społecznych — jedna z pierwszych w Małopolsce federacji organizacji pozarządowych.',
            'allow_indexing'   => false,
            'content_editor'   => 'quill',

            'contact_address' => 'ul. Walerego Sławka 5',
            'contact_city'    => '30-633 Kraków',
            'contact_email'   => 'biuro@krafos.pl',
            'contact_phone'   => '12 421 22 88',
            'contact_intro'   => 'Jeśli masz jakieś pytania, wypełnij formularz kontaktowy, a my niezwłocznie na nie odpowiemy.',
            'contact_show_form' => true,
            'bank_account_number' => 'PL 12 1240 4432 1111 0000 4885 1234',

            'header_layout' => 'default',
            'news_layout'   => 'grid',
            'show_cms_credit' => true,
        ]);

        $settings = SiteSetting::current();
        if (! $settings->logoUrl()) {
            try {
                $settings->addMediaFromUrl('https://krafos.pl/wp-content/uploads/2020/05/logo_krafos_poz.png')
                    ->toMediaCollection('logo');
            } catch (\Throwable $e) {
                // Brak sieci/niedostępne źródło nie powinno wywalać wdrożenia —
                // strona po prostu pokaże zastępczą odznakę zamiast logo.
                report($e);
            }
        }

        $category = Category::firstOrCreate(['slug' => 'projekty'], ['name' => 'Projekty', 'order' => 1]);

        $current = [
            [
                'title' => 'Informator z ofertą NGO',
                'slug' => 'informator-z-oferta-ngo',
                'excerpt' => 'Realizujemy projekt pn. „Aktualizacja Informatora z ofertą organizacji pozarządowych dla mieszkanek i mieszkańców Krakowa".',
                'content' => 'Realizujemy projekt pn. „Aktualizacja Informatora z ofertą organizacji pozarządowych dla mieszkanek i mieszkańców Krakowa". Dzięki zaangażowaniu 92 organizacji pozarządowych opracowaliśmy niezwykle potrzebną publikację.',
            ],
            [
                'title' => 'Pomoc Pokrzywdzonym i Świadkom na 2026 rok – Kraków',
                'slug' => 'pomoc-pokrzywdzonym-i-swiadkom-2026',
                'excerpt' => 'Okręgowy Ośrodek Pomocy Pokrzywdzonym Przestępstwem. Pomagamy osobom pokrzywdzonym, świadkom i ich bliskim rozpocząć nowe życie.',
                'content' => 'Okręgowy Ośrodek Pomocy Pokrzywdzonym Przestępstwem. Pomagamy osobom pokrzywdzonym, świadkom i ich bliskim rozpocząć nowe życie.',
            ],
            [
                'title' => 'Skuteczne NGO',
                'slug' => 'skuteczne-ngo',
                'excerpt' => 'Centrum Obywatelskie ul. Reymonta 20 - edycja 2025-2027. Inspirujemy i wspieramy inicjatywy społeczne na rzecz Krakowa i jego mieszkańców.',
                'content' => 'Centrum Obywatelskie ul. Reymonta 20 - edycja 2025-2027. Inspirujemy i wspieramy inicjatywy społeczne na rzecz Krakowa i jego mieszkańców.',
            ],
        ];

        foreach ($current as $i => $data) {
            Project::updateOrCreate(['slug' => $data['slug']], $data + [
                'category_id' => $category->id,
                'for_whom' => 'Mieszkańcy Krakowa',
                'audience' => 'ngo',
                'is_published' => true,
                'is_completed' => false,
                'order' => $i + 1,
            ]);
        }

        $completed = [
            [
                'title' => 'Centrum Obywatelskie – ul. Reymonta 20. Edycja 2023 – 2025',
                'slug' => 'centrum-obywatelskie-2023-2025',
                'excerpt' => 'Kompleksowe wsparcie infrastrukturalne i merytoryczne dla rozwoju działalności społecznej i obywatelskiej w Krakowie oraz wzmocnienia współpracy wewnątrz i międzysektorowej w zakresie działań na rzecz Krakowa i jego mieszkańców.',
            ],
            [
                'title' => 'Działania na rzecz współpracy międzysektorowej',
                'slug' => 'dzialania-na-rzecz-wspolpracy-miedzysektorowej',
                'excerpt' => 'Krakowskie Forum Organizacji Społecznych KraFOS zrealizowało unikalny projekt „Działania na rzecz współpracy międzysektorowej" – budowanie trwałych partnerstw między organizacjami pozarządowymi, grupami nieformalnymi oraz partnerami z sektora biznesu i administracji publicznej.',
            ],
            [
                'title' => 'Centrum Obywatelskie – ul. Reymonta 20. Edycja 2021 – 2022',
                'slug' => 'centrum-obywatelskie-2021-2022',
                'excerpt' => 'Kompleksowe wsparcie infrastrukturalne i merytoryczne na rzecz rozwoju działalności społecznej i obywatelskiej oraz wzmocnienia współpracy wewnątrz i międzysektorowej w zakresie działań na rzecz Krakowa i jego mieszkańców.',
            ],
            [
                'title' => 'Centrum Obywatelskie – ul. Reymonta 20. Edycja 2019 – 2020',
                'slug' => 'centrum-obywatelskie-2019-2020',
                'excerpt' => 'Działania skoncentrowane na wzroście skuteczności NGO i grup nieformalnych oraz na wzroście zaangażowania obywatelskiego mieszkańców Krakowa poprzez rozwijanie kompetencji, wspieranie współpracy i ułatwianie dostępu do zasobów niezbędnych do prowadzenia działań społecznych i obywatelskich.',
            ],
            [
                'title' => 'Centrum Obywatelskie – Edycja 2017 – 2018',
                'slug' => 'centrum-obywatelskie-2017-2018',
                'excerpt' => 'Zapewnienie kompleksowego wsparcia infrastrukturalnego i merytorycznego dla rozwoju działalności społecznej i obywatelskiej w Krakowie oraz wzmocnienie współpracy wewnątrz i międzysektorowej.',
            ],
        ];

        foreach ($completed as $i => $data) {
            Project::updateOrCreate(['slug' => $data['slug']], $data + [
                'category_id' => $category->id,
                'for_whom' => 'Organizacje pozarządowe',
                'audience' => 'ngo',
                'content' => $data['excerpt'],
                'is_published' => true,
                'is_completed' => true,
                'order' => $i + 101,
            ]);
        }

        // Przykładowy załącznik do pobrania na pierwszym zrealizowanym projekcie
        // (placeholder wygenerowany lokalnie, nie pobierany z zewnątrz).
        $flagshipProject = Project::where('slug', 'centrum-obywatelskie-2023-2025')->first();
        if ($flagshipProject && $flagshipProject->attachments()->count() === 0) {
            $pdfPath = tempnam(sys_get_temp_dir(), 'raport').'.pdf';
            file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\nxref\n0 4\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF");

            $attachment = Attachment::create([
                'attachable_type' => Project::class,
                'attachable_id' => $flagshipProject->id,
                'label' => 'Raport z realizacji projektu',
                'order' => 1,
            ]);
            $attachment->addMedia($pdfPath)->usingFileName('raport-centrum-obywatelskie.pdf')->toMediaCollection('file');
        }

        $news = [
            [
                'title' => 'Pokaż twarz NGO! Zapraszamy organizacje do udziału w spocie promocyjnym',
                'slug' => 'pokaz-twarz-ngo',
                'excerpt' => '20 sekund. Tyle wystarczy, żeby Kraków dowiedział się, że istniejecie. Przygotowujemy spot promujący Informator z ofertą organizacji pozarządowych dla mieszkanek i mieszkańców Krakowa. W spocie wystąpią przedstawiciele organizacji. Wystarczy telefon i kilka minut.',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Ostatnie dni! Nabór do Informatora z ofertą organizacji pozarządowych dla Mieszkanek i Mieszkańców Krakowa przedłużony do 20 sierpnia',
                'slug' => 'nabor-do-informatora-przedluzony',
                'excerpt' => 'Dobra wiadomość – wydłużamy termin zgłoszeń do tegorocznej edycji Informatora z ofertą organizacji pozarządowych dla Mieszkanek i Mieszkańców Krakowa. Na Wasze zgłoszenia czekamy do czwartku, 20 sierpnia 2026 r.',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Podsumowanie roku 2025 — sprawozdanie z działalności KraFOS',
                'slug' => 'podsumowanie-roku-2025',
                'excerpt' => 'Za nami kolejny intensywny rok współpracy z organizacjami pozarządowymi Krakowa. Zapraszamy do zapoznania się ze sprawozdaniem z naszej działalności i najważniejszymi liczbami.',
                'published_at' => now()->subDays(9),
            ],
        ];

        foreach ($news as $data) {
            News::updateOrCreate(['slug' => $data['slug']], $data + [
                'content' => $data['excerpt'],
                'is_published' => true,
            ]);
        }

        $partners = [
            'Fundusz Sprawiedliwości',
            'Ministerstwo Sprawiedliwości',
            'Narodowy Instytut Wolności',
            'Organizacje Pozarządowe (Kraków)',
            'Miasto Kraków',
            'Ośrodek Interwencji Kryzysowej i Poradnictwa',
            'Powiatowe Centrum Pomocy Rodzinie',
            'ROPS Kraków',
            'Policja',
            'Powiat Chrzanowski',
            'GOPS',
        ];

        foreach ($partners as $i => $name) {
            Partner::updateOrCreate(['name' => $name], ['url' => null, 'order' => $i + 1]);
        }

        // Strona FAQ — 3 przykładowe rozwijane pozycje (pytania i odpowiedzi).
        Page::updateOrCreate(['slug' => 'najczesciej-zadawane-pytania'], [
            'title' => 'Najczęściej zadawane pytania',
            'type' => 'faq',
            'is_published' => true,
            'show_in_menu' => false,
            'faq_intro' => 'Odpowiedzi na pytania, które najczęściej zadają nam organizacje zainteresowane członkostwem w KraFOS.',
            'faq_items' => [
                [
                    'question' => 'Jak moja organizacja może dołączyć do KraFOS?',
                    'answer' => '<p>Wystarczy złożyć deklarację członkostwa wraz z uchwałą zarządu o przystąpieniu do federacji oraz aktualnym statutem organizacji. Dokumenty można znaleźć na stronie <a href="/organizacje-czlonkowskie">Organizacje członkowskie</a>.</p>',
                ],
                [
                    'question' => 'Czy członkostwo w federacji jest płatne?',
                    'answer' => '<p>Nie pobieramy składek członkowskich. Członkostwo w KraFOS opiera się na wzajemnym wsparciu i współpracy między organizacjami pozarządowymi.</p>',
                ],
                [
                    'question' => 'Gdzie mogę znaleźć listę organizacji członkowskich?',
                    'answer' => '<p>Pełna lista organizacji zrzeszonych w KraFOS znajduje się na stronie <a href="/organizacje-czlonkowskie">Organizacje członkowskie</a>.</p>',
                ],
            ],
        ]);

        // Strona z dokumentami do pobrania — demonstracja załączników na zwykłej podstronie.
        $docsPage = Page::updateOrCreate(['slug' => 'dokumenty-do-pobrania'], [
            'title' => 'Dokumenty do pobrania',
            'type' => 'standard',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Poniżej znajdziesz dokumenty potrzebne do przystąpienia do Krakowskiego Forum Organizacji Społecznych.</p>',
        ]);

        foreach ([
            ['label' => 'Deklaracja członkostwa', 'file' => 'deklaracja-czlonkostwa.pdf'],
            ['label' => 'Statut KraFOS', 'file' => 'statut-krafos.pdf'],
        ] as $i => $doc) {
            if ($docsPage->attachments()->where('label', $doc['label'])->exists()) {
                continue;
            }

            $pdfPath = tempnam(sys_get_temp_dir(), 'doc').'.pdf';
            file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\nxref\n0 4\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF");

            $attachment = Attachment::create([
                'attachable_type' => Page::class,
                'attachable_id' => $docsPage->id,
                'label' => $doc['label'],
                'order' => $i + 1,
            ]);
            $attachment->addMedia($pdfPath)->usingFileName($doc['file'])->toMediaCollection('file');
        }

        // Strefa członkowska — wewnętrzna strona chroniona hasłem (dostępna infrastruktura
        // CMS-a: typ "internal_hub", jak "Strefa współpracownika"), z linkami dla organizacji
        // członkowskich federacji. Hasło demo: krafos2026.
        Page::updateOrCreate(['slug' => 'strefa-czlonkowska'], [
            'title' => 'Strefa członkowska',
            'type' => 'internal_hub',
            'access_mode' => 'password',
            'access_password' => Hash::make('krafos2026'),
            'is_published' => true,
            'show_in_menu' => false,
            'hub_intro' => 'Materiały i informacje dostępne wyłącznie dla organizacji członkowskich Krakowskiego Forum Organizacji Społecznych.',
            'hub_links' => [
                ['label' => 'Dokumenty do pobrania', 'url' => '/dokumenty-do-pobrania'],
                ['label' => 'Organizacje członkowskie', 'url' => '/organizacje-czlonkowskie'],
                ['label' => 'Najczęściej zadawane pytania', 'url' => '/najczesciej-zadawane-pytania'],
            ],
        ]);

        // Formularz współpracy (moduł "Kreator formularzy") — osadzony na
        // federacyjnej stronie typu "wspolpraca" (demo-wspolpraca).
        \App\Models\FormDefinition::updateOrCreate(['slug' => 'wspolpraca-federacja'], [
            'title' => 'Zgłoś propozycję współpracy',
            'description' => 'Wypełnij formularz, a skontaktujemy się z Tobą w ciągu kilku dni roboczych.',
            'is_active' => true,
            'fields' => [
                ['label' => 'Nazwa organizacji / firmy', 'type' => 'text', 'required' => true],
                ['label' => 'Osoba kontaktowa', 'type' => 'text', 'required' => true],
                ['label' => 'E-mail', 'type' => 'email', 'required' => true],
                ['label' => 'Telefon', 'type' => 'tel', 'required' => false],
                ['label' => 'Wiadomość', 'type' => 'textarea', 'required' => false],
                ['label' => 'Wyrażam zgodę na przetwarzanie danych osobowych w celu obsługi zgłoszenia', 'type' => 'checkbox', 'required' => true],
            ],
        ]);

        // Zespół — realna podstrona (nie demo), powiązana z "O nas". Strona główna
        // (hero.blade.php) linkuje do niej, ale sama treść "O nas" pozostaje
        // bespoke szablonem federacji.
        $zespolPage = Page::updateOrCreate(['slug' => 'zespol'], [
            'title' => 'Zespół',
            'type' => 'about',
            'is_published' => true,
            'show_in_menu' => false,
            'about_intro' => 'Poznaj osoby, które na co dzień koordynują pracę Krakowskiego Forum Organizacji Społecznych.',
        ]);

        foreach ([
            ['title' => 'Anna Nowak', 'person_role' => 'Prezeska Zarządu'],
            ['title' => 'Piotr Wiśniewski', 'person_role' => 'Koordynator ds. współpracy'],
            ['title' => 'Katarzyna Zielińska', 'person_role' => 'Specjalistka ds. projektów'],
        ] as $i => $member) {
            Page::updateOrCreate(
                ['slug' => 'zespol-'.\Illuminate\Support\Str::slug($member['title'])],
                $member + [
                    'parent_id' => $zespolPage->id,
                    'type' => 'about_person',
                    'is_published' => true,
                    'show_in_menu' => false,
                    'order' => $i + 1,
                ]
            );
        }

        // Mapa pomocy — punkty wsparcia organizacji członkowskich w całej Małopolsce
        // (moduł "help_map"), nie tylko w Krakowie.
        foreach ([
            ['name' => 'Punkt wydawania żywności – Kraków Podgórze', 'category' => 'zywnosc', 'address' => 'ul. Wielicka 22, 30-552 Kraków', 'lat' => 50.0304, 'lng' => 19.9497],
            ['name' => 'Jadłodajnia sąsiedzka – Kraków Nowa Huta', 'category' => 'zywnosc', 'address' => 'os. Centrum C 5, 31-925 Kraków', 'lat' => 50.0752, 'lng' => 20.0326],
            ['name' => 'Schronisko dla osób w kryzysie bezdomności – Kraków', 'category' => 'schronienie', 'address' => 'ul. Makuszyńskiego 19, 31-752 Kraków', 'lat' => 50.0819, 'lng' => 20.0157],
            ['name' => 'Poradnia rodzinna – Tarnów', 'category' => 'poradnictwo', 'address' => 'ul. Krakowska 19, 33-100 Tarnów', 'lat' => 50.0121, 'lng' => 20.9858],
            ['name' => 'Punkt poradnictwa obywatelskiego – Nowy Sącz', 'category' => 'poradnictwo', 'address' => 'ul. Jagiellońska 50, 33-300 Nowy Sącz', 'lat' => 49.6222, 'lng' => 20.7143],
            ['name' => 'Bezpłatna pomoc prawna – Chrzanów', 'category' => 'prawo', 'address' => 'Aleja Henryka 20, 32-500 Chrzanów', 'lat' => 50.1372, 'lng' => 19.4020],
            ['name' => 'Centrum zdrowia psychicznego – Oświęcim', 'category' => 'zdrowie', 'address' => 'ul. Wysokie Brzegi 4, 32-600 Oświęcim', 'lat' => 50.0343, 'lng' => 19.2245],
            ['name' => 'Świetlica środowiskowa – Wadowice', 'category' => 'inne', 'address' => 'ul. Lwowska 12, 34-100 Wadowice', 'lat' => 49.8836, 'lng' => 19.4915],
            ['name' => 'Punkt wsparcia dla seniorów – Bochnia', 'category' => 'inne', 'address' => 'ul. Krakowska 3, 32-700 Bochnia', 'lat' => 49.9691, 'lng' => 20.4310],
        ] as $i => $point) {
            \App\Models\HelpPoint::updateOrCreate(
                ['name' => $point['name']],
                $point + ['is_published' => true, 'order' => $i + 1]
            );
        }
    }
}
