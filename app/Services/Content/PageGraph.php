<?php

namespace App\Services\Content;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Graf zależności stron: kto jest czyim rodzicem, które strony linkują do których
 * (linki w treści) i które są podpięte pod działania.
 *
 * Strony z menu głównego (pozycje w Menu nawigacji albo „pokaż w menu”) są stronami głównymi:
 * w grafie wychodzą od wspólnego węzła „Menu główne”, który w hierarchii stoi na samej górze.
 *
 * Do tego wnioski dla redakcji: strony bez żadnego wejścia (ani w menu, ani rodzica,
 * ani linku z innej strony) oraz opublikowane strony linkujące do szkiców
 * (dla odwiedzających taki link prowadzi donikąd).
 */
class PageGraph
{
    /** Typy-pozycje menu nie mają treści ani własnej strony — w grafie są zbędne. */
    private const SKIP_TYPES = ['redirect', 'menu_link', 'menu_heading'];

    /**
     * @return array{nodes: array, edges: array, warnings: array, rows: Collection}
     */
    public function build(): array
    {
        $pages = Page::query()->forCurrentSite()
            ->whereNotIn('type', self::SKIP_TYPES)
            ->orderBy('parent_id')->orderBy('order')->orderBy('title')
            ->get(['id', 'title', 'slug', 'type', 'parent_id', 'project_id', 'is_published', 'show_in_menu', 'content']);

        $types = Page::types();
        $byPath = [];
        $urls = [];
        foreach ($pages as $page) {
            $url = $this->safeUrl($page);
            $urls[$page->id] = $url;
            foreach (array_filter([$url ? $this->path($url) : null, '/'.$page->slug]) as $path) {
                $byPath[$path] ??= $page->id;
            }
        }

        $edges = [];
        $out = [];
        $in = [];

        // Strony główne: wskazane przez pozycje menu głównego (pozycje najwyższego poziomu przed podpozycjami) i „pokaż w menu”.
        $menuIds = [];
        $navItems = NavItem::query()->forCurrentSite()->where('location', 'main')->where('is_active', true)
            ->orderByRaw('parent_id is not null')->orderBy('order')->get(['url', 'parent_id']);
        foreach ($navItems as $nav) {
            $path = $nav->url && $nav->url !== '#' ? $this->path($nav->url) : null;
            if ($path !== null && isset($byPath[$path])) {
                $menuIds[$byPath[$path]] = true;
            }
        }
        foreach ($pages as $page) {
            if ($page->show_in_menu && ! $page->parent_id) {
                $menuIds[$page->id] = true;
            }
        }
        foreach (array_keys($menuIds) as $id) {
            $edges[] = ['source' => 'menu', 'target' => 'p'.$id, 'kind' => 'menu'];
        }
        foreach ($pages as $page) {
            if ($page->parent_id && $pages->contains('id', $page->parent_id)) {
                $edges[] = ['source' => 'p'.$page->parent_id, 'target' => 'p'.$page->id, 'kind' => 'parent'];
            }
            foreach ($this->linkedIds((string) $page->content, $byPath) as $target) {
                if ($target === $page->id) {
                    continue;
                }
                $edges[] = ['source' => 'p'.$page->id, 'target' => 'p'.$target, 'kind' => 'link'];
                $out[$page->id][] = $target;
                $in[$target][] = $page->id;
            }
        }

        $projectIds = $pages->pluck('project_id')->filter()->unique();
        $projects = $projectIds->isEmpty() ? collect() : Project::query()->whereIn('id', $projectIds)->get(['id', 'title', 'slug']);
        foreach ($pages->whereNotNull('project_id') as $page) {
            if ($projects->contains('id', $page->project_id)) {
                $edges[] = ['source' => 'd'.$page->project_id, 'target' => 'p'.$page->id, 'kind' => 'project'];
            }
        }

        $titles = $pages->pluck('title', 'id');
        $published = $pages->pluck('is_published', 'id');
        $warnings = ['orphans' => [], 'draft_links' => []];
        $rows = collect();
        $nodes = [];

        foreach ($pages as $page) {
            $linksOut = array_values(array_unique($out[$page->id] ?? []));
            $linksIn = array_values(array_unique($in[$page->id] ?? []));
            $inMenu = $page->show_in_menu || isset($menuIds[$page->id]);
            $orphan = ! $page->parent_id && ! $inMenu && ! $page->project_id && ! $linksIn && $page->slug !== 'strona-glowna';
            if ($orphan && $page->is_published) {
                $warnings['orphans'][] = $page->id;
            }
            if ($page->is_published) {
                foreach ($linksOut as $target) {
                    if (! ($published[$target] ?? true)) {
                        $warnings['draft_links'][] = ['from' => $page->id, 'to' => $target];
                    }
                }
            }

            $typeLabel = trim(\Illuminate\Support\Str::before($types[$page->type] ?? 'Standardowa', ' ('));
            $nodes[] = ['data' => [
                'id' => 'p'.$page->id,
                'label' => $page->title,
                'kind' => 'page',
                'type' => $typeLabel,
                'published' => (bool) $page->is_published,
                'menu' => $inMenu,
                'main' => isset($menuIds[$page->id]),
                'orphan' => $orphan,
                'url' => $urls[$page->id],
                'edit' => route('admin.podstrony.edit', $page->id),
                'out' => count($linksOut),
                'in' => count($linksIn),
            ]];

            $rows->push([
                'id' => $page->id,
                'title' => $page->title,
                'type' => $typeLabel,
                'published' => (bool) $page->is_published,
                'menu' => $inMenu,
                'parent' => $page->parent_id ? ($titles[$page->parent_id] ?? null) : null,
                'project' => $page->project_id ? $projects->firstWhere('id', $page->project_id)?->title : null,
                'links_out' => array_map(fn ($id) => ['id' => $id, 'title' => $titles[$id] ?? '?'], $linksOut),
                'links_in' => array_map(fn ($id) => ['id' => $id, 'title' => $titles[$id] ?? '?'], $linksIn),
                'orphan' => $orphan,
                'edit' => route('admin.podstrony.edit', $page->id),
            ]);
        }

        foreach ($projects as $project) {
            $nodes[] = ['data' => [
                'id' => 'd'.$project->id,
                'label' => $project->title,
                'kind' => 'project',
                'type' => 'Działanie',
                'published' => true,
                'edit' => Route::has('admin.projekty.edit') ? route('admin.projekty.edit', $project) : null,
            ]];
        }

        if ($menuIds) {
            $nodes[] = ['data' => ['id' => 'menu', 'label' => 'Menu główne', 'kind' => 'menu', 'type' => 'Menu', 'published' => true,
                'edit' => Route::has('admin.pozycje-menu.index') ? route('admin.pozycje-menu.index') : null]];
        }

        // Ta sama para stron może łączyć się kilkoma linkami — w grafie wystarczy jedna krawędź danego rodzaju.
        $edges = collect($edges)->unique(fn ($e) => $e['kind'].$e['source'].'>'.$e['target'])
            ->values()->map(fn ($e, $i) => ['data' => $e + ['id' => 'e'.$i]])->all();

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'warnings' => [
                'orphans' => array_map(fn ($id) => ['id' => $id, 'title' => $titles[$id]], $warnings['orphans']),
                'draft_links' => array_map(fn ($w) => ['from' => $titles[$w['from']], 'from_id' => $w['from'], 'to' => $titles[$w['to']]], $warnings['draft_links']),
            ],
            'rows' => $rows,
            'counts' => [
                'pages' => $pages->count(),
                'links' => collect($edges)->where('data.kind', 'link')->count(),
                'projects' => $projects->count(),
            ],
        ];
    }

    /** Id stron, do których prowadzą linki w treści (adresy względne i z domeną serwisu). */
    private function linkedIds(string $html, array $byPath): array
    {
        if ($html === '' || ! preg_match_all('/href\s*=\s*(["\'])(.*?)\1/i', $html, $m)) {
            return [];
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        $ids = [];
        foreach ($m[2] as $href) {
            $href = html_entity_decode(trim($href));
            if ($href === '' || preg_match('#^(mailto:|tel:|\#|javascript:)#i', $href)) {
                continue;
            }
            $hrefHost = parse_url($href, PHP_URL_HOST);
            if ($hrefHost && $host && strcasecmp($hrefHost, $host) !== 0) {
                continue; // link zewnętrzny
            }
            $path = $this->path($href);
            if ($path !== null && isset($byPath[$path])) {
                $ids[] = $byPath[$path];
            }
        }

        return array_values(array_unique($ids));
    }

    private function path(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        return '/'.trim(rawurldecode($path), '/');
    }

    private function safeUrl(Page $page): ?string
    {
        try {
            return $page->publicUrl();
        } catch (Throwable) {
            return null;
        }
    }
}
