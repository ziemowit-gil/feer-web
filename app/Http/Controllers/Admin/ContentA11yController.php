<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Services\Content\ContentA11yChecker;
use App\Services\Content\ContentA11yFixer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Dostępność treści: sprawdzanie na żywo z edytora (panel boczny edycji) i raport
 * dla całego serwisu — strony, aktualności, działania. Uzupełnia audyt opisów
 * alternatywnych i dokumentów w bibliotece plików.
 *
 * Autonaprawa (fix()) poprawia automatycznie to, co nie wymaga decyzji redaktora
 * (ContentA11yFixer); każdy zapis zostawia wersję w historii zmian.
 *
 * Metody: check(), index(), fix().
 */
class ContentA11yController extends Controller
{
    /** Typy treści w raporcie: moduł (uprawnienia), etykieta, trasa edycji. */
    private const SOURCES = [
        'pages' => ['module' => 'pages', 'label' => 'Strony', 'one' => 'Strona', 'route' => 'admin.podstrony.edit'],
        'news' => ['module' => 'news', 'label' => 'Aktualności', 'one' => 'Aktualność', 'route' => 'admin.newsy.edit'],
        'projects' => ['module' => 'projects', 'label' => 'Działania', 'one' => 'Działanie', 'route' => 'admin.projekty.edit'],
    ];

    /** Sprawdza HTML z edytora (bez zapisu) — odpowiedź z uwagami i gotowym fragmentem listy do panelu. */
    public function check(Request $request, ContentA11yChecker $checker): JsonResponse
    {
        $data = $request->validate([
            'html' => ['nullable', 'string', 'max:2000000'],
        ]);

        $issues = $checker->check($data['html'] ?? '');

        return response()->json([
            'summary' => ContentA11yChecker::summary($issues),
            'html' => view('admin.content-a11y.issues', ['issues' => $issues])->render(),
        ]);
    }

    /** Raport dla całego serwisu: treści z uwagami, od największej liczby błędów. */
    public function index(Request $request, ContentA11yChecker $checker, ContentA11yFixer $fixer)
    {
        $user = $request->user();
        $sources = collect(self::SOURCES)->filter(fn ($s) => $user->canAccessModule($s['module']));
        $type = $sources->has($request->input('typ')) ? $request->input('typ') : null;
        $rule = array_key_exists((string) $request->input('regula'), ContentA11yChecker::RULES) ? $request->input('regula') : null;
        $fixer = $fixer->only($rule ? [$rule] : []);

        $rows = collect();
        foreach ($sources as $key => $source) {
            if ($type && $type !== $key) {
                continue;
            }
            foreach ($this->records($key) as $record) {
                $issues = $checker->check($record->content);
                if ($rule) {
                    $issues = array_values(array_filter($issues, fn ($i) => $i['rule'] === $rule));
                }
                if (! $issues) {
                    continue;
                }
                $fixable = ($rule && ! isset(ContentA11yFixer::RULE_MAP[$rule])) ? [] : $fixer->fixRecord($record);
                $rows->push([
                    'key' => $key,
                    'id' => $record->id,
                    'fixable' => $fixable,
                    'type' => $source['one'],
                    'title' => $record->title,
                    'published' => (bool) $record->is_published,
                    'edit_url' => Route::has($source['route']) ? route($source['route'], $record) : null,
                    'issues' => $issues,
                    'summary' => ContentA11yChecker::summary($issues),
                ]);
            }
        }

        $rows = $rows->sortBy([
            fn ($a, $b) => $b['summary']['errors'] <=> $a['summary']['errors'],
            fn ($a, $b) => $b['summary']['warnings'] <=> $a['summary']['warnings'],
        ])->values();

        $byRule = $rows->flatMap(fn ($r) => array_column($r['issues'], 'rule'))->countBy()->sortDesc();

        return view('admin.content-a11y.index', [
            'rows' => $rows,
            'byRule' => $byRule,
            'sources' => $sources,
            'type' => $type,
            'rule' => $rule,
            'totals' => [
                'records' => $rows->count(),
                'errors' => $rows->sum('summary.errors'),
                'warnings' => $rows->sum('summary.warnings'),
                'fixable' => $rows->filter(fn ($r) => $r['fixable'])->count(),
            ],
        ]);
    }

    /** Rekordy danego typu z bieżącej witryny, z treścią HTML. */
    private function records(string $key): Collection
    {
        $query = match ($key) {
            'pages' => Page::query()->forCurrentSite()->whereNotIn('type', ['redirect', 'menu_link', 'menu_heading']),
            'news' => News::query()->forCurrentSite(),
            'projects' => Project::query()->forCurrentSite(),
        };

        return $query->whereNotNull('content')->where('content', '!=', '')
            ->orderByDesc('is_published')->orderBy('title')
            ->get(['id', 'title', 'slug', 'content', 'blocks', 'is_published']);
    }

    /**
     * Autonaprawa: poprawia automatycznie jedną treść (typ + id) albo wszystkie
     * z bieżącego filtra (typ, reguła). Linków i opisów obrazów nie rusza.
     */
    public function fix(Request $request, ContentA11yFixer $fixer)
    {
        $user = $request->user();
        $sources = collect(self::SOURCES)->filter(fn ($s) => $user->canAccessModule($s['module']));
        $data = $request->validate([
            'typ' => ['nullable', 'string'],
            'id' => ['nullable', 'integer'],
            'regula' => ['nullable', 'string'],
        ]);
        $type = $sources->has($data['typ'] ?? null) ? $data['typ'] : null;
        abort_if(filled($data['id'] ?? null) && ! $type, 404);
        $rule = array_key_exists((string) ($data['regula'] ?? ''), ContentA11yChecker::RULES) ? $data['regula'] : null;
        $fixer = $fixer->only($rule ? [$rule] : []);

        $records = 0;
        $totals = [];
        foreach ($sources as $key => $source) {
            if ($type && $type !== $key) {
                continue;
            }
            $ids = $this->records($key)->pluck('id');
            if (filled($data['id'] ?? null)) {
                abort_unless($ids->contains((int) $data['id']), 404);
                $ids = collect([(int) $data['id']]);
            }
            $model = ['pages' => Page::class, 'news' => News::class, 'projects' => Project::class][$key];
            foreach ($model::query()->whereIn('id', $ids)->get() as $record) {
                $fixes = $fixer->fixRecord($record, apply: true);
                if ($fixes) {
                    $records++;
                    foreach ($fixes as $k => $n) {
                        $totals[$k] = ($totals[$k] ?? 0) + $n;
                    }
                }
            }
        }

        $status = $records
            ? 'Poprawiono automatycznie '.$records.' '.trans_choice('treść|treści|treści', $records).': '
                .collect($totals)->map(fn ($n, $k) => ContentA11yFixer::FIXES[$k].' ('.$n.')')->implode(', ')
                .'. Każdą zmianę cofniesz w historii wersji treści. Linki i opisy obrazów popraw ręcznie.'
            : 'Nie było nic do poprawienia automatycznie. Pozostałe uwagi (linki, opisy obrazów) popraw ręcznie.';

        return redirect()->route('admin.content-a11y.index', array_filter(['typ' => $request->input('back_typ'), 'regula' => $request->input('back_regula')]))
            ->with('status', $status);
    }
}
