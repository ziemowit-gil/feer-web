<?php

namespace App\Support;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Stan pozycji menu w drzewie menu (jak ostrzeżenia w module „Strona” TYPO3): czy pozycja jest widoczna,
 * dokąd prowadzi i czy cel jest dostępny dla odwiedzających — szkic, wyłączona strona, adres bez strony,
 * moduł wyłączony w ustawieniach.
 *
 * Wynik dla pozycji: ['state' => …, 'page' => ?Page, 'kind' => …, 'issue' => bool, 'label' => opis stanu].
 * `state` (ważniejszy wygrywa): hidden, module_off, page_missing, page_disabled, page_draft, page_scheduled,
 * page_restricted, ok. `kind` (dokąd prowadzi): page, module, home, anchor, external, mail, phone, none.
 * `issue` = pozycja widoczna w menu, a prowadzi do strony niedostępnej dla odwiedzających.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MenuLinkCheck
{
    /**
     * @param  iterable<NavItem>  $items
     * @return array<int, array{state: string, page: ?Page, kind: string, issue: bool, label: string}>
     */
    public static function forItems(iterable $items): array
    {
        $items = collect($items);
        // Strony po slugu jednym zapytaniem (ostatni człon adresu = slug, jak w NavItem::editablePage()).
        $slugs = $items->map(fn (NavItem $i) => self::path((string) $i->url))->filter()
            ->map(fn (string $p) => Str::afterLast($p, '/'))->unique()->values();
        // Ten sam slug może być w kilku witrynach sieci — pierwszeństwo ma strona bieżącej witryny.
        $siteId = SiteSetting::current()->id;
        $pages = $slugs->isEmpty() ? collect() : Page::query()->whereIn('slug', $slugs)
            ->get(['id', 'site_id', 'title', 'slug', 'parent_id', 'is_published', 'is_disabled', 'publish_at', 'unpublish_at', 'access_level', 'type'])
            ->sortBy(fn (Page $p) => (int) $p->site_id === (int) $siteId ? 0 : 1)
            ->unique('slug')->keyBy('slug');

        $out = [];
        foreach ($items as $item) {
            $out[$item->id] = self::check($item, $pages);
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Page>  $pages
     * @return array{state: string, page: ?Page, kind: string, issue: bool, label: string}
     */
    private static function check(NavItem $item, $pages): array
    {
        $url = trim((string) $item->url);
        $path = self::path($url);
        $page = $path !== null && $path !== '' ? $pages->get(Str::afterLast($path, '/')) : null;

        $kind = match (true) {
            in_array($item->type, ['dropdown', 'projects', 'pages'], true) && ($url === '' || $url === '#') => 'none',
            in_array($item->type, NavItem::MODULE_TYPES, true) => 'module',
            $url === '' || $url === '#' => 'none',
            Str::startsWith($url, '#') => 'anchor',
            Str::startsWith($url, 'mailto:') => 'mail',
            Str::startsWith($url, 'tel:') => 'phone',
            $path === null => 'external',
            $path === '' => 'home',
            $page !== null => 'page',
            default => 'module',
        };

        $moduleOff = filled($item->module) && ! SiteSetting::current()->isModuleEnabled((string) $item->module);
        $restricted = $page && $page->access_level && ! in_array($page->access_level, ['inherit', 'public'], true);
        // Adres wewnętrzny bez strony: brak trasy albo trasa stron („/{slug}”) bez strony o tym slugu.
        $missing = $kind === 'module' && ! in_array($item->type, NavItem::MODULE_TYPES, true) && ! self::routeExists((string) $path);

        [$state, $label] = match (true) {
            ! $item->is_active => ['hidden', 'ukryta w menu'],
            $moduleOff => ['module_off', 'niewidoczna — moduł „'.(SiteSetting::MODULES[$item->module] ?? $item->module).'” jest wyłączony'],
            $missing => ['page_missing', 'adres nie prowadzi do żadnej strony'],
            $page && $page->is_disabled => ['page_disabled', 'strona „'.$page->title.'” jest wyłączona'],
            $page && ! $page->is_published => ['page_draft', 'strona „'.$page->title.'” to szkic'],
            $page && $page->publish_at && $page->publish_at->isFuture() => ['page_scheduled', 'strona „'.$page->title.'” opublikuje się '.$page->publish_at->format('d.m.Y H:i')],
            (bool) $restricted => ['page_restricted', 'strona z ograniczonym dostępem: '.(Page::ACCESS_LEVELS[$page->access_level] ?? $page->access_level)],
            default => ['ok', 'widoczna'],
        };

        return [
            'state' => $state,
            'page' => $page,
            'kind' => $kind === 'module' && $missing ? 'none' : $kind,
            'issue' => in_array($state, ['page_missing', 'page_disabled', 'page_draft', 'page_scheduled'], true),
            'label' => $label,
        ];
    }

    /** Ikona i klasa koloru nakładki stanu na ikonie pozycji (null = bez nakładki). */
    public static function overlay(string $state): ?array
    {
        return [
            'hidden' => ['fa-eye-slash', 'text-muted'],
            'module_off' => ['fa-ban', 'text-red-700'],
            'page_missing' => ['fa-link-slash', 'text-red-700'],
            'page_disabled' => ['fa-ban', 'text-red-700'],
            'page_draft' => ['fa-pen', 'text-amber-800'],
            'page_scheduled' => ['fa-clock', 'text-brand-dark'],
            'page_restricted' => ['fa-key', 'text-ink'],
        ][$state] ?? null;
    }

    /**
     * Ścieżka adresu wewnętrznego bez ukośników na brzegach ('' = strona główna) albo null dla adresu
     * zewnętrznego, kotwicy, poczty, telefonu i pustego.
     */
    public static function path(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || $url === '#' || Str::startsWith($url, ['#', 'mailto:', 'tel:', 'javascript:'])) {
            return null;
        }
        if (Str::startsWith($url, ['http://', 'https://', '//'])) {
            $base = rtrim(url('/'), '/');
            if (! Str::startsWith($url, $base)) {
                return null;
            }
            $url = Str::after($url, $base);
        }

        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    /** Czy pod ścieżką jest strona modułu (trasa inna niż ogólna trasa stron „/{slug}”). */
    private static function routeExists(string $path): bool
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create('/'.$path, 'GET'));
        } catch (\Throwable) {
            return false;
        }
        $name = (string) $route->getName();

        return ! ($name === '' && str_contains((string) $route->uri(), '{'))
            && ! Str::startsWith($name, ['page.', 'site.page']) && ! str_contains($name, '.page.');
    }
}
