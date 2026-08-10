<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FounderPageSeeder extends Seeder
{
    public function run(): void
    {
        $about = Page::where('type', 'about')->orWhere('slug', 'o-organizacji')->first();

        if (! $about) {
            $this->command->error('Nie znaleziono strony O organizacji (typ "about" lub slug "o-organizacji"). Seeder pominięty.');
            return;
        }

        $existing = Page::where('parent_id', $about->id)
            ->where('type', 'about_person')
            ->where('is_featured', true)
            ->first();

        if ($existing) {
            $this->command->info('Strona fundatora juz istnieje: ' . $existing->title . ' (id: ' . $existing->id . '). Seeder pominienty.');
            return;
        }

        $personPart = 'fundator';
        $prefix = $about->slug . '/osoba';
        $slug = "{$prefix}/{$personPart}";
        $suffix = 2;
        while (Page::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$prefix}/{$personPart}-{$suffix}";
            $suffix++;
        }

        $page = Page::create([
            'type'        => 'about_person',
            'parent_id'   => $about->id,
            'title'       => 'Imię Nazwisko Fundatora',
            'slug'        => $slug,
            'person_role' => 'Fundator FEER',
            'content'     => '<p>Tu wpisz cytat lub krótkie wprowadzenie fundatora, które pojawi się w sekcji „Słowo od Fundatora" na stronie O organizacji. Możesz go edytować w panelu admina.</p>',
            'is_published' => true,
            'is_featured'  => true,
            'order'        => 0,
        ]);

        $this->command->info('Strona fundatora utworzona: ' . $page->title . ' (id: ' . $page->id . ', slug: ' . $page->slug . ').');
        $this->command->warn('Pamietaj uzupelnic imie i nazwisko, zdjecie oraz tresc w panelu admina > Strony > ' . $page->title . '.');
    }
}
