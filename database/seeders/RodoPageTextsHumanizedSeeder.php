<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Strona „Dane osobowe: klauzule informacyjne” (slug rodo): tytuł strony i etykiety plików do pobrania bez myślnika
 * w roli dwukropka („Klauzula informacyjna: formularz kontaktowy”). Ukrywa też listę „Pliki do pobrania” na tej stronie
 * (layout_data.hide_attachments), bo powtarza klauzule w wersji PDF.
 *
 * Idempotentny: zmienia tylko pola o dokładnie pierwotnym brzmieniu, więc nie nadpisze zmian z panelu.
 *
 *   php artisan db:seed --class=RodoPageTextsHumanizedSeeder --force
 */
class RodoPageTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $changed = 0;

        $page = Page::withoutGlobalScopes()->where('slug', 'rodo')->first();
        if ($page && $page->title === 'Dane osobowe - klauzule informacyjne') {
            $page->title = 'Dane osobowe: klauzule informacyjne';
            $page->save();
            $changed++;
        }

        // Lista „Pliki do pobrania” duplikuje klauzule w wersji PDF, więc na tej stronie jest ukryta (pliki zostają w panelu).
        if ($page && empty(($page->layout_data ?? [])['hide_attachments'])) {
            $layout = $page->layout_data ?? [];
            $layout['hide_attachments'] = true;
            $page->layout_data = $layout;
            $page->save();
            $changed++;
        }

        $labels = [
            'Klauzula informacyjna - formularz kontaktowy' => 'Klauzula informacyjna: formularz kontaktowy',
            'Klauzula informacyjna - Szkolenia i Konsultacje' => 'Klauzula informacyjna: Szkolenia i Konsultacje',
        ];
        foreach (Attachment::query()->whereIn('label', array_keys($labels))->get() as $attachment) {
            $attachment->label = $labels[$attachment->label];
            $attachment->save();
            $changed++;
        }

        $this->command?->info($changed > 0 ? "RODO: zmieniono {$changed} tekstów." : 'Pominięto: teksty RODO były już zmieniane.');
    }
}
