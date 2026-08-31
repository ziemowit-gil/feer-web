<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Category;
use App\Models\News;
use App\Models\Partner;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

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
    }
}
