<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\NavItem;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Poll;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SiteSettingSeeder::class,
        ]);

        $this->cleanDemoData();

        // ── Hero slides ───────────────────────────────────────────────────────
        $slides = [
            ['url' => 'https://images.unsplash.com/photo-1560184897-ae75f418493e?auto=format&fit=crop&w=1400&q=80', 'title' => 'Wspieramy dostępność cyfrową dla wszystkich', 'text' => 'Audytujemy, szkolimy i rozwijamy narzędzia zgodne ze standardami WCAG.'],
            ['url' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80', 'title' => 'Platforma vLAB już dostępna dla szkół', 'text' => 'Wirtualne laboratoria szkoleniowe uruchamiane w chmurze, bez barier.'],
            ['url' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1400&q=80', 'title' => 'Otwarta baza wiedzy o dostępności', 'text' => 'Podręczniki i checklisty, z których korzysta już ponad 100 organizacji.'],
        ];

        foreach ($slides as $i => $slide) {
            $hero = HeroSlide::create(['title' => $slide['title'], 'text' => $slide['text'], 'order' => $i]);
            try {
                $hero->addMediaFromUrl($slide['url'])
                    ->usingFileName("hero_{$i}.jpg")
                    ->toMediaCollection('image');
            } catch (\Throwable) {}
        }

        // ── Galeria ───────────────────────────────────────────────────────────
        $galleryUrls = [
            'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=500&q=80',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=500&q=80',
            'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=500&q=80',
            'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=500&q=80',
        ];

        foreach ($galleryUrls as $i => $url) {
            $img = GalleryImage::create(['caption' => 'Zdjęcie z działań fundacji', 'order' => $i]);
            try {
                $img->addMediaFromUrl($url)->usingFileName("gallery_{$i}.jpg")->toMediaCollection('image');
            } catch (\Throwable) {}
        }

        // ── Strony ────────────────────────────────────────────────────────────
        $oOrganizacji = Page::updateOrCreate(['slug' => 'o-organizacji'], [
            'title'        => 'O organizacji',
            'content'      => '<p>Fundacja FEER działa na rzecz dostępności cyfrowej i edukacji technologicznej.</p><p>Prowadzimy audyty WCAG, szkolenia oraz rozwijamy otwarte narzędzia, z których korzystają szkoły, urzędy i organizacje pozarządowe w całej Polsce.</p>',
            'is_published' => true,
            'order'        => 0,
        ]);

        Page::updateOrCreate(['slug' => 'historia'], [
            'title'        => 'Historia',
            'content'      => '<p>Fundacja FEER powstała z inicjatywy zespołu specjalistów ds. dostępności cyfrowej.</p><p>Od tego czasu przeprowadziliśmy dziesiątki audytów WCAG, uruchomiliśmy platformę vLAB oraz zbudowaliśmy otwartą bazę wiedzy dostępną dla wszystkich organizacji.</p>',
            'is_published' => true,
            'order'        => 1,
        ]);

        Page::updateOrCreate(['slug' => 'zespol'], [
            'title'        => 'Zespół',
            'parent_id'    => $oOrganizacji->id,
            'content'      => '<p>Fundację tworzy zespół specjalistów ds. dostępności cyfrowej, edukacji i rozwoju oprogramowania.</p>',
            'is_published' => true,
            'order'        => 0,
        ]);

        Page::updateOrCreate(['slug' => 'statut'], [
            'title'        => 'Statut',
            'parent_id'    => $oOrganizacji->id,
            'content'      => '<p>Pełna treść statutu fundacji zostanie opublikowana wkrótce.</p>',
            'is_published' => true,
            'order'        => 1,
        ]);

        // ── Projekty ──────────────────────────────────────────────────────────
        $categories = [
            ['name' => 'Audyty i WCAG', 'projects' => [
                ['title' => 'Audyt dostępności portalu miejskiego',       'excerpt' => 'Pełna ocena zgodności z WCAG 2.2 dla portalu urzędu miasta.'],
                ['title' => 'Wdrożenie poprawek dla biblioteki cyfrowej', 'excerpt' => 'Wsparcie techniczne przy naprawie barier w bibliotece cyfrowej.'],
            ]],
            ['name' => 'Platforma vLAB', 'projects' => [
                ['title' => 'Wirtualne laboratorium chemiczne',        'excerpt' => 'Symulacje eksperymentów chemicznych dostępne w przeglądarce.'],
                ['title' => 'Środowisko szkoleniowe dla nauczycieli', 'excerpt' => 'Gotowe scenariusze zajęć z zakresu dostępności cyfrowej.'],
            ]],
            ['name' => 'Edukacja i szkolenia', 'projects' => [
                ['title' => 'Cykl warsztatów WCAG dla samorządów', 'excerpt' => 'Comiesięczne szkolenia dla pracowników urzędów.'],
            ]],
        ];

        foreach ($categories as $catIdx => $catData) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($catData['name'])],
                ['name' => $catData['name'], 'order' => $catIdx]
            );

            foreach ($catData['projects'] as $projIdx => $proj) {
                Project::updateOrCreate(
                    ['slug' => Str::slug($proj['title'])],
                    [
                        'category_id'  => $category->id,
                        'title'        => $proj['title'],
                        'excerpt'      => $proj['excerpt'],
                        'content'      => '<p>'.$proj['excerpt'].'</p><p>Szczegółowy opis zostanie uzupełniony przez zespół fundacji.</p>',
                        'is_published' => true,
                        'order'        => $projIdx,
                    ]
                );
            }
        }

        // ── Aktualności ───────────────────────────────────────────────────────
        $newsCats = [
            NewsCategory::updateOrCreate(['slug' => 'dostepnosc'], ['name' => 'Dostępność', 'order' => 0]),
            NewsCategory::updateOrCreate(['slug' => 'edukacja'],   ['name' => 'Edukacja',   'order' => 1]),
            NewsCategory::updateOrCreate(['slug' => 'statutowe'],  ['name' => 'Statutowe',  'order' => 2]),
        ];

        $newsItems = [
            ['category' => $newsCats[0], 'title' => 'Jak przeprowadzić audyt WCAG krok po kroku',          'excerpt' => 'Praktyczny przewodnik po ocenie zgodności serwisu ze standardami dostępności.',         'image' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=800&q=80', 'imageAlt' => 'Osoba pracująca przy laptopie podczas przeglądu kodu strony.',              'tags' => ['wcag', 'audyt', 'dostępność'], 'days' => 5],
            ['category' => $newsCats[1], 'title' => 'Nowe warsztaty w programie vLAB dla nauczycieli',      'excerpt' => 'Rozpoczynamy kolejną edycję szkoleń z wirtualnych środowisk laboratoryjnych.',          'image' => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=800&q=80', 'imageAlt' => 'Nauczyciele podczas warsztatów w sali komputerowej.',                       'tags' => ['vlab', 'edukacja', 'szkolenia'], 'days' => 15],
            ['category' => $newsCats[2], 'title' => 'Podsumowanie kwartalne działań fundacji',              'excerpt' => 'Transparentne zestawienie zrealizowanych celów technicznych i merytorycznych.',       'image' => 'https://images.unsplash.com/photo-1508921912186-1d1a45ebb3c1?auto=format&fit=crop&w=800&q=80', 'imageAlt' => 'Zespół fundacji podczas spotkania podsumowującego kwartalne działania.', 'tags' => ['fundacja', 'raport'], 'days' => 30],
        ];

        foreach ($newsItems as $item) {
            $news = News::updateOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'news_category_id' => $item['category']->id,
                    'title'            => $item['title'],
                    'excerpt'          => $item['excerpt'],
                    'image_alt'        => $item['imageAlt'],
                    'content'          => '<p>'.$item['excerpt'].'</p><p>Pełna treść zostanie uzupełniona przez zespół fundacji.</p>',
                    'published_at'     => now()->subDays($item['days']),
                    'is_published'     => true,
                ]
            );

            if (! $news->getFirstMedia('image')) {
                try {
                    $news->addMediaFromUrl($item['image'])
                        ->usingFileName(Str::slug($item['title']).'.jpg')
                        ->toMediaCollection('image');
                } catch (\Throwable) {}
            }

            $tagIds = collect($item['tags'])->map(
                fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id
            );
            $news->tags()->sync($tagIds);
        }

        // ── Nawigacja ─────────────────────────────────────────────────────────
        $navItems = [
            ['label' => 'O organizacji', 'url' => '/o-organizacji', 'module' => 'pages',    'order' => 10],
            ['label' => 'Historia',      'url' => '/historia',       'module' => 'pages',    'order' => 20],
            ['label' => 'Projekty',      'type' => 'projects',       'module' => 'projects', 'order' => 30],
            ['label' => 'Aktualności',   'url' => '/aktualnosci',    'module' => 'news',     'order' => 40],
            ['label' => 'Galeria zdjęć', 'url' => '#galeria',        'module' => 'gallery',  'order' => 50],
            ['label' => 'Kontakt',       'url' => '#kontakt',                                'order' => 60],
            ['label' => 'Wesprzyj',      'url' => '#kontakt',        'is_button' => true,    'order' => 70],
        ];

        foreach ($navItems as $item) {
            NavItem::firstOrCreate(
                ['label' => $item['label'], 'url' => $item['url'] ?? null],
                array_merge(['is_active' => true], $item)
            );
        }

        // ── Ankieta ───────────────────────────────────────────────────────────
        if (! Poll::where('question', 'Czy zapiszesz się na szkolenie z dostępności stron www?')->exists()) {
            $poll = Poll::create(['question' => 'Czy zapiszesz się na szkolenie z dostępności stron www?', 'is_active' => true]);
            $poll->options()->createMany([
                ['label' => 'Tak',     'votes' => 25, 'order' => 0],
                ['label' => 'Nie',     'votes' => 15, 'order' => 1],
                ['label' => 'Nie wiem','votes' => 60, 'order' => 2],
            ]);
        }

        // ── Partnerzy ─────────────────────────────────────────────────────────
        foreach ([['name' => 'ePUAP', 'url' => 'https://epuap.gov.pl'], ['name' => 'SEKAP', 'url' => 'https://www.sekap.pl']] as $i => $data) {
            $partner = Partner::firstOrCreate(['name' => $data['name']], ['url' => $data['url'], 'order' => $i]);
            if (! $partner->getFirstMedia('logo')) {
                try {
                    $partner->addMediaFromUrl('https://placehold.co/200x80/png?text='.urlencode($data['name']))
                        ->usingFileName(Str::slug($data['name']).'.png')
                        ->toMediaCollection('logo');
                } catch (\Throwable) {}
            }
        }

        $this->call([
            EventSeeder::class,
            BipDocumentSeeder::class,
            FaqSeeder::class,
            VolunteerAdSeeder::class,
            AnnualReportSeeder::class,
            EducationalMaterialSeeder::class,
            QuickActionSeeder::class,
            SubscriptionPlansSeeder::class,
            JrwaClassSeeder::class,
        ]);

        $this->printAccessSummary();
    }

    private function printAccessSummary(): void
    {
        $url = rtrim(config('app.url', 'http://localhost'), '/');

        $this->command->newLine();
        $this->command->line('  <fg=bright-cyan>╔══════════════════════════════════════════════════════╗</>');
        $this->command->line('  <fg=bright-cyan>║</>  <fg=bright-white;options=bold>Dane dostępowe demo</>                                  <fg=bright-cyan>║</>');
        $this->command->line('  <fg=bright-cyan>╠══════════════════════════════════════════════════════╣</>');
        $this->command->line('  <fg=bright-cyan>║</>  <fg=yellow>Strona:</>                                               <fg=bright-cyan>║</>');
        $this->command->line("  <fg=bright-cyan>║</>    {$url}                                    <fg=bright-cyan>║</>");
        $this->command->line('  <fg=bright-cyan>║</>  <fg=yellow>Panel admina:</>                                        <fg=bright-cyan>║</>');
        $this->command->line("  <fg=bright-cyan>║</>    {$url}/admin                               <fg=bright-cyan>║</>");
        $this->command->line('  <fg=bright-cyan>╠══════════════════════════════════════════════════════╣</>');
        $this->command->line('  <fg=bright-cyan>║</>  <fg=yellow>Użytkownicy</>  (hasło: <fg=bright-white>demo12(@</>)                         <fg=bright-cyan>║</>');
        $this->command->line('  <fg=bright-cyan>║</>                                                    <fg=bright-cyan>║</>');
        $this->command->line('  <fg=bright-cyan>║</>  <fg=green>admin@demo.feer.org.pl</>        Administrator          <fg=bright-cyan>║</>');
        $this->command->line('  <fg=bright-cyan>║</>  <fg=green>redaktor@demo.feer.org.pl</>     Redaktor               <fg=bright-cyan>║</>');
        $this->command->line('  <fg=bright-cyan>║</>  <fg=green>bip@demo.feer.org.pl</>          Edytor BIP             <fg=bright-cyan>║</>');
        $this->command->line('  <fg=bright-cyan>╚══════════════════════════════════════════════════════╝</>');
        $this->command->newLine();
    }

    private function cleanDemoData(): void
    {
        HeroSlide::query()->delete();
        GalleryImage::query()->delete();
    }
}
