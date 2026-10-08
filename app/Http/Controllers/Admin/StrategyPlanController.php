<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StrategyPlanRequest;
use App\Models\StrategyActionType;
use App\Models\StrategyFundingSource;
use App\Models\StrategyPlan;
use App\Models\StrategyStatuteRef;
use App\Models\StrategyTargetGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Panel admin: moduł „Strategia organizacji" — planowanie działań operacyjnych
 * w układzie miesiąc × grupa docelowa × typ działania × statut × finansowanie.
 *
 * Widok index() renderuje aplikację Alpine.js; pozostałe metody to endpointy
 * JSON (fetch) do pobierania listy z podsumowaniami oraz CRUD planów.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class StrategyPlanController extends Controller
{
    /** Ekran główny modułu — słowniki i konfiguracja dla komponentu Alpine. */
    public function index(): View
    {
        return view('admin.strategy.index', [
            'targetGroups'   => StrategyTargetGroup::active()->get(['id', 'name']),
            'actionTypes'    => StrategyActionType::active()->get(['id', 'name', 'icon']),
            'statuteRefs'    => StrategyStatuteRef::active()->get(['id', 'code', 'title']),
            'fundingSources' => StrategyFundingSource::active()->get(['id', 'name', 'kind']),
            'users'          => User::orderBy('name')->get(['id', 'name']),
            'statuses'       => StrategyPlan::STATUSES,
            'months'         => StrategyPlan::MONTHS,
            'resourceTypes'  => \App\Models\StrategyPlanResource::TYPES,
            'currentYear'    => (int) now()->year,
        ]);
    }

    /**
     * Lista planów danego roku (JSON) z filtrami oraz podsumowaniem
     * budżetu i liczby działań per miesiąc i per grupa docelowa.
     */
    public function list(Request $request): JsonResponse
    {
        $year = (int) $request->integer('year', (int) now()->year);

        $query = StrategyPlan::with(['actionType:id,name,icon', 'statuteRef:id,code,title', 'fundingSource:id,name,kind', 'targetGroups:id,name', 'resources', 'creator:id,name'])
            ->forYear($year)
            ->orderBy('month')
            ->orderBy('title');

        // Filtry opcjonalne — spójne z kontrolkami nad tabelą.
        if ($request->filled('month')) {
            $query->where('month', (int) $request->integer('month'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('action_type_id')) {
            $query->where('action_type_id', (int) $request->integer('action_type_id'));
        }
        if ($request->filled('funding_source_id')) {
            $query->where('funding_source_id', (int) $request->integer('funding_source_id'));
        }
        if ($request->filled('target_group_id')) {
            $groupId = (int) $request->integer('target_group_id');
            $query->whereHas('targetGroups', fn ($q) => $q->where('strategy_target_groups.id', $groupId));
        }
        if ($request->filled('q')) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->string('q'))) . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term));
        }

        $plans = $query->get();

        return response()->json([
            'plans'   => $plans->map(fn (StrategyPlan $plan) => $this->planPayload($plan))->values(),
            'summary' => $this->buildSummary($plans),
        ]);
    }

    /** Zapisuje nowy plan działania wraz z grupami i zasobami. */
    public function store(StrategyPlanRequest $request): JsonResponse
    {
        $plan = DB::transaction(function () use ($request) {
            $plan = StrategyPlan::create(
                $this->planAttributes($request) + ['created_by' => $request->user()->id]
            );
            $plan->targetGroups()->sync($request->validated('target_groups'));
            $this->syncResources($plan, $request->validated('resources') ?? []);

            return $plan;
        });

        return response()->json([
            'message' => 'Działanie zostało dodane do planu.',
            'plan'    => $this->planPayload($plan->fresh(['actionType', 'statuteRef', 'fundingSource', 'targetGroups', 'resources', 'creator'])),
        ], 201);
    }

    /** Aktualizuje plan działania wraz z grupami i zasobami. */
    public function update(StrategyPlanRequest $request, StrategyPlan $plan): JsonResponse
    {
        DB::transaction(function () use ($request, $plan) {
            $plan->update($this->planAttributes($request));
            $plan->targetGroups()->sync($request->validated('target_groups'));
            $this->syncResources($plan, $request->validated('resources') ?? []);
        });

        return response()->json([
            'message' => 'Działanie zostało zaktualizowane.',
            'plan'    => $this->planPayload($plan->fresh(['actionType', 'statuteRef', 'fundingSource', 'targetGroups', 'resources', 'creator'])),
        ]);
    }

    /** Usuwa plan działania (pozycje zasobów i pivoty kasują się kaskadowo). */
    public function destroy(StrategyPlan $plan): JsonResponse
    {
        $plan->delete();

        return response()->json(['message' => 'Działanie zostało usunięte z planu.']);
    }

    /** Atrybuty planu wspólne dla store() i update(). */
    private function planAttributes(StrategyPlanRequest $request): array
    {
        return $request->safe()->only([
            'title', 'description', 'year', 'month', 'status',
            'action_type_id', 'statute_ref_id', 'funding_source_id', 'budget_planned',
        ]);
    }

    /** Zastępuje pozycje alokacji zasobów planu przesłaną tablicą. */
    private function syncResources(StrategyPlan $plan, array $resources): void
    {
        $plan->resources()->delete();

        foreach ($resources as $resource) {
            $plan->resources()->create([
                'type'     => $resource['type'],
                'name'     => $resource['name'],
                'user_id'  => $resource['user_id'] ?? null,
                'quantity' => $resource['quantity'] ?? 1,
                'unit'     => $resource['unit'] ?? null,
                'cost'     => $resource['cost'] ?? null,
                'note'     => $resource['note'] ?? null,
            ]);
        }
    }

    /** Płaska reprezentacja planu dla frontendu Alpine. */
    private function planPayload(StrategyPlan $plan): array
    {
        return [
            'id'                => $plan->id,
            'title'             => $plan->title,
            'description'       => $plan->description,
            'year'              => $plan->year,
            'month'             => $plan->month,
            'status'            => $plan->status,
            'status_label'      => StrategyPlan::STATUSES[$plan->status] ?? $plan->status,
            'action_type_id'    => $plan->action_type_id,
            'action_type'       => $plan->actionType?->only(['id', 'name', 'icon']),
            'statute_ref_id'    => $plan->statute_ref_id,
            'statute_ref'       => $plan->statuteRef?->only(['id', 'code', 'title']),
            'funding_source_id' => $plan->funding_source_id,
            'funding_source'    => $plan->fundingSource?->only(['id', 'name', 'kind']),
            'budget_planned'    => (float) $plan->budget_planned,
            'target_groups'     => $plan->targetGroups->map->only(['id', 'name'])->values(),
            'resources'         => $plan->resources->map(fn ($r) => [
                'type'     => $r->type,
                'name'     => $r->name,
                'user_id'  => $r->user_id,
                'quantity' => (float) $r->quantity,
                'unit'     => $r->unit,
                'cost'     => $r->cost !== null ? (float) $r->cost : null,
                'note'     => $r->note,
            ])->values(),
            'creator'           => $plan->creator?->name,
        ];
    }

    /**
     * Agregaty do paska podsumowań: łączna liczba działań i budżet,
     * rozbicie na miesiące (1–12) oraz na grupy docelowe.
     * Uwaga: działanie wielogrupowe liczy się w każdej ze swoich grup.
     */
    private function buildSummary($plans): array
    {
        $perMonth = [];
        foreach (range(1, 12) as $month) {
            $monthPlans = $plans->where('month', $month);
            $perMonth[] = [
                'month'  => $month,
                'count'  => $monthPlans->count(),
                'budget' => round((float) $monthPlans->sum('budget_planned'), 2),
            ];
        }

        $perGroup = [];
        foreach ($plans as $plan) {
            foreach ($plan->targetGroups as $group) {
                $perGroup[$group->id] ??= ['id' => $group->id, 'name' => $group->name, 'count' => 0, 'budget' => 0.0];
                $perGroup[$group->id]['count']++;
                $perGroup[$group->id]['budget'] = round($perGroup[$group->id]['budget'] + (float) $plan->budget_planned, 2);
            }
        }
        usort($perGroup, fn ($a, $b) => $b['budget'] <=> $a['budget']);

        return [
            'total_count'  => $plans->count(),
            'total_budget' => round((float) $plans->sum('budget_planned'), 2),
            'per_month'    => $perMonth,
            'per_group'    => array_values($perGroup),
        ];
    }
}
