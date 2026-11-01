<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Trzy przykładowe szkolenia w module „Szkolenia i zajęcia” (/zajecia) witryny w motywie FEER IS, każde z terminami
 * w najbliższych tygodniach. Bez cen (pole ceny puste, czyli zajęcia bezpłatne do czasu ustalenia opłat). Do zastąpienia
 * prawdziwą ofertą. Idempotentny: szkolenie o tym samym adresie w witrynie nie jest tworzone drugi raz.
 *
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsSampleCoursesSeeder --force   (slug albo nazwa witryny; domyślnie „szkolenia”)
 */
class FeerIsSampleCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? ''))) ?: 'szkolenia';
        $site = SiteSetting::query()->notTrashed()->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

            return;
        }

        $samples = [
            [
                'slug' => 'komputer-z-czytnikiem-ekranu', 'title' => 'Komputer z czytnikiem ekranu', 'category' => 'tyflo', 'format' => 'stationary', 'featured' => true,
                'lead' => 'Nauka pracy na komputerze z czytnikiem ekranu: od uruchomienia systemu po pracę z dokumentami i internetem.',
                'description' => '<p>Na zajęciach uczysz się obsługiwać komputer za pomocą czytnika ekranu i skrótów klawiszowych. Zaczynamy od podstaw, a tempo dopasowujemy do grupy.</p><p>Przy zapisie możesz opisać, z jakiego sprzętu i programów korzystasz, żebyśmy przygotowali zajęcia.</p>',
                'editions' => [['weeks' => 3, 'location' => 'Siedziba instytucji']],
            ],
            [
                'slug' => 'dostepne-dokumenty-word-i-pdf', 'title' => 'Dostępne dokumenty Word i PDF', 'category' => 'wcag', 'format' => 'online', 'featured' => true,
                'lead' => 'Jak przygotować dokumenty, z których skorzystają także osoby używające czytników ekranu.',
                'description' => '<p>Szkolenie pokazuje, jak ustawić style i nagłówki, opisać obrazy i sprawdzić dostępność gotowego pliku. Pracujemy na przykładach dokumentów uczestników.</p>',
                'editions' => [['weeks' => 4, 'location' => null], ['weeks' => 8, 'location' => null]],
            ],
            [
                'slug' => 'microsoft-365-w-codziennej-pracy', 'title' => 'Microsoft 365 w codziennej pracy', 'category' => 'm365', 'format' => 'hybrid', 'featured' => false,
                'lead' => 'Poczta, kalendarz, Teams i dokumenty w chmurze: praktyczne zastosowania w pracy zespołu.',
                'description' => '<p>Na zajęciach ćwiczysz codzienne zadania w Outlooku, Teams i Wordzie online. Szkolenie można odbyć na miejscu albo zdalnie.</p>',
                'editions' => [['weeks' => 5, 'location' => 'Siedziba instytucji i online']],
            ],
        ];

        $created = 0;
        foreach ($samples as $i => $sample) {
            if (Course::withoutGlobalScopes()->where('site_id', $site->id)->where('slug', $sample['slug'])->exists()) {
                continue;
            }

            $course = (new Course)->forceFill([
                'site_id' => $site->id, 'title' => $sample['title'], 'slug' => $sample['slug'], 'lead' => $sample['lead'],
                'description' => $sample['description'], 'category' => $sample['category'], 'format' => $sample['format'],
                'price' => null, 'status' => 'active', 'is_featured' => $sample['featured'], 'order' => $i + 1,
            ]);
            $course->save();

            foreach ($sample['editions'] as $edition) {
                (new CourseEdition)->forceFill([
                    'course_id' => $course->id, 'starts_on' => now()->addWeeks($edition['weeks'])->startOfWeek()->toDateString(),
                    'ends_on' => now()->addWeeks($edition['weeks'] + 3)->startOfWeek()->toDateString(), 'location' => $edition['location'],
                    'capacity' => 10, 'enrollment_status' => 'open',
                ])->save();
            }

            $created++;
        }

        $this->command?->info($created > 0
            ? "Utworzono {$created} przykładowych szkoleń w module „Szkolenia i zajęcia” witryny „".($site->site_name ?: $slug).'”.'
            : 'Pominięto: przykładowe szkolenia już istnieją.');
    }
}
