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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SiteSettingSeeder::class,
        ]);

        $slides = [
            ['url' => 'https://images.unsplash.com/photo-1560184897-ae75f418493e?auto=format&fit=crop&w=1400&q=80', 'title' => 'Wspieramy dostępność cyfrową dla wszystkich', 'text' => 'Audytujemy, szkolimy i rozwijamy narzędzia zgodne ze standardami WCAG.'],
            ['url' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80', 'title' => 'Platforma vLAB już dostępna dla szkół', 'text' => 'Wirtualne laboratoria szkoleniowe uruchamiane w chmurze, bez barier.'],
            ['url' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1400&q=80', 'title' => 'Otwarta baza wiedzy o dostępności', 'text' => 'Podręczniki i checklisty, z których korzysta już ponad 100 organizacji.'],
        ];

        foreach ($slides as $i => $slide) {
            $path = $this->downloadImage($slide['url'], 'hero');

            if ($path) {
                HeroSlide::create([
                    'image_path' => $path,
                    'title' => $slide['title'],
                    'text' => $slide['text'],
                    'order' => $i,
                ]);
            }
        }

        $galleryUrls = [
            'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=500&q=80',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=500&q=80',
            'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=500&q=80',
            'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=500&q=80',
        ];

        foreach ($galleryUrls as $i => $url) {
            $path = $this->downloadImage($url, 'gallery');

            if ($path) {
                GalleryImage::create([
                    'image_path' => $path,
                    'caption' => 'Zdjęcie z działań fundacji',
                    'order' => $i,
                ]);
            }
        }

        $oOrganizacji = Page::create([
            'title' => 'O organizacji',
            'slug' => 'o-organizacji',
            'content' => '<p>Fundacja FEER działa na rzecz dostępności cyfrowej i edukacji technologicznej.</p><p>Prowadzimy audyty WCAG, szkolenia oraz rozwijamy otwarte narzędzia, z których korzystają szkoły, urzędy i organizacje pozarządowe w całej Polsce.</p>',
            'is_published' => true,
            'order' => 0,
        ]);

        Page::create([
            'title' => 'Historia',
            'slug' => 'historia',
            'content' => '<p>Fundacja FEER powstała z inicjatywy zespołu specjalistów ds. dostępności cyfrowej.</p><p>Od tego czasu przeprowadziliśmy dziesiątki audytów WCAG, uruchomiliśmy platformę vLAB oraz zbudowaliśmy otwartą bazę wiedzy dostępną dla wszystkich organizacji.</p>',
            'is_published' => true,
            'order' => 1,
        ]);

        Page::create([
            'title' => 'Zespół',
            'slug' => 'zespol',
            'parent_id' => $oOrganizacji->id,
            'content' => '<p>Fundację tworzy zespół specjalistów ds. dostępności cyfrowej, edukacji i rozwoju oprogramowania.</p>',
            'is_published' => true,
            'order' => 0,
        ]);

        Page::create([
            'title' => 'Statut',
            'slug' => 'statut',
            'parent_id' => $oOrganizacji->id,
            'content' => '<p>Pełna treść statutu fundacji zostanie opublikowana wkrótce.</p>',
            'is_published' => true,
            'order' => 1,
        ]);

        $categories = [
            [
                'name' => 'Audyty i WCAG',
                'projects' => [
                    ['title' => 'Audyt dostępności portalu miejskiego', 'excerpt' => 'Pełna ocena zgodności z WCAG 2.2 dla portalu urzędu miasta.'],
                    ['title' => 'Wdrożenie poprawek dla biblioteki cyfrowej', 'excerpt' => 'Wsparcie techniczne przy naprawie barier w bibliotece cyfrowej.'],
                ],
            ],
            [
                'name' => 'Platforma vLAB',
                'projects' => [
                    ['title' => 'Wirtualne laboratorium chemiczne', 'excerpt' => 'Symulacje eksperymentów chemicznych dostępne w przeglądarce.'],
                    ['title' => 'Środowisko szkoleniowe dla nauczycieli', 'excerpt' => 'Gotowe scenariusze zajęć z zakresu dostępności cyfrowej.'],
                ],
            ],
            [
                'name' => 'Edukacja i szkolenia',
                'projects' => [
                    ['title' => 'Cykl warsztatów WCAG dla samorządów', 'excerpt' => 'Comiesięczne szkolenia dla pracowników urzędów.'],
                ],
            ],
        ];

        foreach ($categories as $categoryIndex => $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'order' => $categoryIndex,
            ]);

            foreach ($categoryData['projects'] as $projectIndex => $projectData) {
                Project::create([
                    'category_id' => $category->id,
                    'title' => $projectData['title'],
                    'slug' => Str::slug($projectData['title']),
                    'excerpt' => $projectData['excerpt'],
                    'content' => '<p>'.$projectData['excerpt'].'</p><p>Szczegółowy opis projektu zostanie uzupełniony przez zespół fundacji.</p>',
                    'is_published' => true,
                    'order' => $projectIndex,
                ]);
            }
        }

        $newsCategories = [
            NewsCategory::create(['name' => 'Dostępność', 'slug' => 'dostepnosc', 'order' => 0]),
            NewsCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja', 'order' => 1]),
            NewsCategory::create(['name' => 'Statutowe', 'slug' => 'statutowe', 'order' => 2]),
        ];

        $newsItems = [
            [
                'category' => $newsCategories[0],
                'title' => 'Jak przeprowadzić audyt WCAG krok po kroku',
                'excerpt' => 'Praktyczny przewodnik po ocenie zgodności serwisu ze standardami dostępności.',
                'image' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=800&q=80',
                'imageAlt' => 'Osoba pracująca przy laptopie podczas przeglądu kodu strony internetowej.',
                'tags' => ['wcag', 'audyt', 'dostępność'],
                'publishedAt' => now()->subDays(5),
            ],
            [
                'category' => $newsCategories[1],
                'title' => 'Nowe warsztaty w programie vLAB dla nauczycieli',
                'excerpt' => 'Rozpoczynamy kolejną edycję szkoleń z wirtualnych środowisk laboratoryjnych.',
                'image' => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=800&q=80',
                'imageAlt' => 'Nauczyciele podczas warsztatów szkoleniowych w sali komputerowej.',
                'tags' => ['vlab', 'edukacja', 'szkolenia'],
                'publishedAt' => now()->subDays(15),
            ],
            [
                'category' => $newsCategories[2],
                'title' => 'Podsumowanie kwartalne działań fundacji',
                'excerpt' => 'Transparentne zestawienie zrealizowanych celów technicznych i merytorycznych.',
                'image' => 'https://images.unsplash.com/photo-1508921912186-1d1a45ebb3c1?auto=format&fit=crop&w=800&q=80',
                'imageAlt' => 'Zespół fundacji podczas spotkania podsumowującego kwartalne działania.',
                'tags' => ['fundacja', 'raport'],
                'publishedAt' => now()->subDays(30),
            ],
        ];

        foreach ($newsItems as $item) {
            $imagePath = $this->downloadImage($item['image'], 'news');

            $news = News::create([
                'news_category_id' => $item['category']->id,
                'title' => $item['title'],
                'slug' => Str::slug($item['title']),
                'excerpt' => $item['excerpt'],
                'image_alt' => $item['imageAlt'],
                'content' => '<p>'.$item['excerpt'].'</p><p>Pełna treść newsa zostanie uzupełniona przez zespół fundacji.</p>',
                'image_path' => $imagePath,
                'published_at' => $item['publishedAt'],
                'is_published' => true,
            ]);

            $news->refreshImageDimensions();

            $tagIds = collect($item['tags'])->map(
                fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id
            );

            $news->tags()->sync($tagIds);
        }

        NavItem::create(['label' => 'O organizacji', 'url' => '/o-organizacji', 'module' => 'pages', 'is_active' => true, 'order' => 10]);
        NavItem::create(['label' => 'Historia', 'url' => '/historia', 'module' => 'pages', 'is_active' => true, 'order' => 20]);
        NavItem::create(['label' => 'Projekty', 'type' => 'projects', 'module' => 'projects', 'is_active' => true, 'order' => 30]);
        NavItem::create(['label' => 'Aktualności', 'url' => '/aktualnosci', 'module' => 'news', 'is_active' => true, 'order' => 40]);
        NavItem::create(['label' => 'Galeria zdjęć', 'url' => '#galeria', 'module' => 'gallery', 'is_active' => true, 'order' => 50]);
        NavItem::create(['label' => 'Kontakt', 'url' => '#kontakt', 'is_active' => true, 'order' => 60]);
        NavItem::create(['label' => 'Wesprzyj', 'url' => '#kontakt', 'is_button' => true, 'is_active' => true, 'order' => 70]);

        $poll = Poll::create([
            'question' => 'Czy zapiszesz się na szkolenie z dostępności stron www?',
            'is_active' => true,
        ]);

        $poll->options()->createMany([
            ['label' => 'Tak', 'votes' => 25, 'order' => 0],
            ['label' => 'Nie', 'votes' => 15, 'order' => 1],
            ['label' => 'Nie wiem', 'votes' => 60, 'order' => 2],
        ]);

        $partners = [
            ['name' => 'ePUAP', 'url' => 'https://epuap.gov.pl'],
            ['name' => 'SEKAP', 'url' => 'https://www.sekap.pl'],
        ];

        foreach ($partners as $i => $data) {
            $partner = Partner::create(['name' => $data['name'], 'url' => $data['url'], 'order' => $i]);
            $partner->addMediaFromUrl('https://placehold.co/200x80/png?text='.urlencode($data['name']))
                ->usingFileName(Str::slug($data['name']).'.png')
                ->toMediaCollection('logo');
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
        ]);
    }

    private function downloadImage(string $url, string $directory): ?string
    {
        $response = Http::timeout(15)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $filename = $directory.'/'.uniqid().'.jpg';
        Storage::disk('public')->put($filename, $response->body());

        return $filename;
    }
}
