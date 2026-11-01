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

        $terms = '<h2>Zapisy</h2><p>Zapisujesz się przez formularz przy wybranym terminie. Po zgłoszeniu potwierdzamy udział e-mailem. Gdy brakuje miejsc, trafiasz na listę rezerwową.</p>'
            .'<h2>Płatność</h2><p>Jeśli szkolenie jest płatne, dane do przelewu wysyłamy po potwierdzeniu udziału. Cena obejmuje materiały szkoleniowe.</p>'
            .'<h2>Rezygnacja</h2><p>Z udziału możesz zrezygnować, pisząc do nas przed rozpoczęciem zajęć.</p>'
            .'<h2>Zaświadczenie</h2><p>Zaświadczenie o ukończeniu wydajemy po udziale w zajęciach w zakresie opisanym w programie.</p>';
        $faq = [
            ['question' => 'Czy mogę zgłosić potrzebę dostosowania zajęć?', 'answer' => 'Tak. Opisz ją w formularzu zapisu, a skontaktujemy się z Tobą przed zajęciami.'],
            ['question' => 'Co się dzieje, gdy nie ma wolnych miejsc?', 'answer' => 'Trafiasz na listę rezerwową. Gdy zwolni się miejsce albo ogłosimy nowy termin, damy Ci znać.'],
        ];

        $samples = [
            [
                'slug' => 'komputer-z-czytnikiem-ekranu', 'title' => 'Komputer z czytnikiem ekranu', 'category' => 'tyflo', 'format' => 'stationary', 'featured' => true,
                'lead' => 'Nauka pracy na komputerze z czytnikiem ekranu: od uruchomienia systemu po pracę z dokumentami i internetem.',
                'description' => '<p>Na zajęciach uczysz się obsługiwać komputer za pomocą czytnika ekranu i skrótów klawiszowych. Zaczynamy od podstaw, a tempo dopasowujemy do grupy.</p><p>Przy zapisie możesz opisać, z jakiego sprzętu i programów korzystasz, żebyśmy przygotowali zajęcia.</p>',
                'duration' => '20 godzin dydaktycznych', 'level' => 'podstawowy', 'certificate' => 'zaświadczenie o ukończeniu',
                'audience' => [['text' => 'Osoby niewidome i słabowidzące, które zaczynają pracę z komputerem']],
                'outcomes' => [['text' => 'Uruchomisz komputer i programy za pomocą klawiatury'], ['text' => 'Napiszesz i zapiszesz dokument'], ['text' => 'Znajdziesz informacje w internecie i wyślesz e-mail']],
                'program' => [['title' => 'Czytnik ekranu i klawiatura', 'hours' => '6', 'text' => 'Uruchamianie, poruszanie się po systemie, skróty.'], ['title' => 'Dokumenty', 'hours' => '8', 'text' => 'Pisanie, formatowanie i zapisywanie plików.'], ['title' => 'Internet i poczta', 'hours' => '6', 'text' => 'Przeglądarka, wyszukiwanie, poczta elektroniczna.']],
                'prerequisites' => 'Nie potrzebujesz wcześniejszego doświadczenia. Jeśli masz własny komputer, przynieś go na zajęcia.',
                'editions' => [['weeks' => 3, 'location' => 'Siedziba instytucji']],
            ],
            [
                'slug' => 'dostepne-dokumenty-word-i-pdf', 'title' => 'Dostępne dokumenty Word i PDF', 'category' => 'wcag', 'format' => 'online', 'featured' => true,
                'lead' => 'Jak przygotować dokumenty, z których skorzystają także osoby używające czytników ekranu.',
                'description' => '<p>Szkolenie pokazuje, jak ustawić style i nagłówki, opisać obrazy i sprawdzić dostępność gotowego pliku. Pracujemy na przykładach dokumentów uczestników.</p>',
                'duration' => '8 godzin dydaktycznych', 'level' => 'podstawowy', 'certificate' => 'zaświadczenie o ukończeniu', 'funding' => 'możliwe z BUR i KFS po spełnieniu warunków operatora',
                'audience' => [['text' => 'Pracownicy urzędów, instytucji i organizacji'], ['text' => 'Osoby przygotowujące materiały dla odbiorców']],
                'outcomes' => [['text' => 'Ustawisz style i nagłówki w dokumencie'], ['text' => 'Dodasz tekst alternatywny do obrazów i opiszesz tabele'], ['text' => 'Sprawdzisz dostępność gotowego pliku PDF']],
                'program' => [['title' => 'Struktura dokumentu', 'hours' => '3', 'text' => 'Style, nagłówki, listy i kolejność czytania.'], ['title' => 'Obrazy, tabele i linki', 'hours' => '3', 'text' => 'Teksty alternatywne, proste tabele, opisy linków.'], ['title' => 'Sprawdzanie i eksport do PDF', 'hours' => '2', 'text' => 'Narzędzia kontroli, eksport i poprawki.']],
                'editions' => [['weeks' => 4, 'location' => null], ['weeks' => 8, 'location' => null]],
            ],
            [
                'slug' => 'microsoft-365-w-codziennej-pracy', 'title' => 'Microsoft 365 w codziennej pracy', 'category' => 'm365', 'format' => 'hybrid', 'featured' => false,
                'lead' => 'Poczta, kalendarz, Teams i dokumenty w chmurze: praktyczne zastosowania w pracy zespołu.',
                'description' => '<p>Na zajęciach ćwiczysz codzienne zadania w Outlooku, Teams i Wordzie online. Szkolenie można odbyć na miejscu albo zdalnie.</p>',
                'duration' => '12 godzin dydaktycznych', 'level' => 'średnio zaawansowany', 'certificate' => 'zaświadczenie o ukończeniu',
                'audience' => [['text' => 'Zespoły, które korzystają z Microsoft 365 w pracy']],
                'outcomes' => [['text' => 'Uporządkujesz pocztę i kalendarz w Outlooku'], ['text' => 'Poprowadzisz spotkanie i pracę zespołu w Teams'], ['text' => 'Będziesz pracować na wspólnych dokumentach w chmurze']],
                'program' => [['title' => 'Outlook', 'hours' => '4', 'text' => 'Poczta, kalendarz, zadania.'], ['title' => 'Teams', 'hours' => '4', 'text' => 'Spotkania, kanały, pliki zespołu.'], ['title' => 'Dokumenty w chmurze', 'hours' => '4', 'text' => 'Word i Excel online, udostępnianie, wersje.']],
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
                'duration' => $sample['duration'] ?? null, 'level' => $sample['level'] ?? null, 'certificate' => $sample['certificate'] ?? null, 'funding' => $sample['funding'] ?? null,
                'audience' => $sample['audience'] ?? null, 'outcomes' => $sample['outcomes'] ?? null, 'program' => $sample['program'] ?? null,
                'prerequisites' => $sample['prerequisites'] ?? null, 'terms' => $terms, 'faq' => $faq,
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
