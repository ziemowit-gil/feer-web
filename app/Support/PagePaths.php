<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Str;
use Throwable;

/**
 * Adresy zależne od drzewa stron. Strony mają płaskie adresy (/{slug}), więc przeniesienie strony
 * w drzewie nie zmienia jej adresu — wyjątkiem są strony osób, których adres zawiera adres strony
 * nadrzędnej ({rodzic}/osoba/{imie-nazwisko}). Tu dbamy, żeby nadążały:
 *
 *  - zmiana adresu strony → jej strony osób dostają nowy prefiks,
 *  - przeniesienie strony osoby pod inną stronę → nowy prefiks rodzica.
 *
 * Każda zmiana zapisuje się zwykłym save(), więc RedirectsOldSlug (App\Support\SlugRedirects)
 * dodaje przekierowanie 301 ze starego adresu (dla opublikowanych stron).
 */
class PagePaths
{
    /** Wywoływane po zapisie strony (Page::booted). */
    public static function afterUpdate(Page $page): void
    {
        try {
            if ($page->isAboutPerson()) {
                if ($page->wasChanged('parent_id')) {
                    self::rebase($page);
                }

                return;
            }

            if ($page->wasChanged('slug') && filled($page->getOriginal('slug'))) {
                self::rebaseChildren($page, (string) $page->getOriginal('slug'));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Strony osób tej strony: prefiks „{stary-adres}/osoba/” → „{nowy-adres}/osoba/”. */
    private static function rebaseChildren(Page $parent, string $oldSlug): void
    {
        $persons = Page::query()->withoutGlobalScope('editor_scope')
            ->where('parent_id', $parent->id)->where('type', 'about_person')->get()
            ->filter(fn (Page $p) => str_starts_with((string) $p->slug, $oldSlug.'/osoba/'))
            ->values();
        if ($persons->isEmpty()) {
            return;
        }

        $parentNotice = session('slug_redirect');
        foreach ($persons as $person) {
            self::rebase($person, $parent);
        }
        // Komunikat dla redaktora mówi o zmianie adresu strony, nie ostatniej osoby.
        if ($parentNotice) {
            session()->flash('slug_redirect', $parentNotice.' Zmieniliśmy też adresy stron osób tej strony ('.$persons->count().'), stare adresy przekierowują na nowe.');
        }
    }

    /** Nowy adres strony osoby pod obecnym rodzicem (ta sama końcówka z imieniem, w razie kolizji z numerem). */
    private static function rebase(Page $person, ?Page $parent = null): void
    {
        $parent ??= $person->parent_id ? Page::query()->withoutGlobalScope('editor_scope')->find($person->parent_id) : null;
        $personPart = Str::afterLast((string) $person->slug, '/') ?: (Str::slug((string) $person->title) ?: 'osoba');
        $prefix = $parent ? $parent->slug.'/osoba' : 'osoba';
        $slug = self::unique($prefix, $personPart, $person->id);

        if ($slug !== $person->slug) {
            $person->slug = $slug;
            $person->save();
        }
    }

    private static function unique(string $prefix, string $personPart, int $ignoreId): string
    {
        $base = "{$prefix}/{$personPart}";
        $slug = $base;
        for ($suffix = 2; Page::withTrashed()->withoutGlobalScope('editor_scope')->where('slug', $slug)->where('id', '!=', $ignoreId)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
