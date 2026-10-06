<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\StrategyActionType;
use App\Models\StrategyFundingSource;
use App\Models\StrategyPlan;
use App\Models\StrategyStatuteRef;
use App\Models\StrategyTargetGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moduł „Strategia organizacji": CRUD planów działań (JSON), walidacja 422,
 * filtry i podsumowania oraz zarządzanie słownikami (w tym blokada usuwania
 * pozycji użytych w planach).
 */
class StrategyModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Moduł jest domyślnie wyłączony (opt-in) — testy CRUD włączają go jawnie.
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['disabled_modules' => []]);
    }

    public function test_module_is_disabled_by_default(): void
    {
        SiteSetting::current()->update(['disabled_modules' => ['strategy']]);

        $this->actingAs($this->admin())
            ->get(route('admin.strategia.index'))
            ->assertOk()
            ->assertDontSee('strategyApp')
            ->assertSee('wyłączony');

        $this->assertStringContainsString(
            '"strategy"',
            (new SiteSetting())->getAttributes()['disabled_modules'] ?? '',
            'Nowy rekord ustawień powinien startować z wyłączonym modułem strategii.'
        );
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Minimalny, poprawny payload planu na bazie pozycji zaszytych w migracji. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title'             => 'Warsztaty cyfrowe dla seniorów',
            'description'       => 'Cykl spotkań.',
            'year'              => 2026,
            'month'             => 5,
            'status'            => 'planned',
            'action_type_id'    => StrategyActionType::first()->id,
            'statute_ref_id'    => StrategyStatuteRef::first()->id,
            'funding_source_id' => StrategyFundingSource::first()->id,
            'budget_planned'    => 4500,
            'target_groups'     => [StrategyTargetGroup::first()->id],
            'resources'         => [
                ['type' => 'human', 'name' => 'Trenerka', 'quantity' => 16, 'unit' => 'godz.'],
                ['type' => 'financial', 'name' => 'Catering', 'cost' => 800],
            ],
        ], $overrides);
    }

    public function test_index_renders_for_logged_in_user(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.strategia.index'))
            ->assertOk()
            ->assertSee('strategyApp', false);
    }

    public function test_store_creates_plan_with_groups_and_resources(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.strategia.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('plan.title', 'Warsztaty cyfrowe dla seniorów')
            ->assertJsonCount(1, 'plan.target_groups')
            ->assertJsonCount(2, 'plan.resources');

        $this->assertDatabaseHas('strategy_plans', ['title' => 'Warsztaty cyfrowe dla seniorów', 'month' => 5]);
        $this->assertDatabaseCount('strategy_plan_resources', 2);
    }

    public function test_store_validates_required_fields_as_json_422(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.strategia.store'), ['title' => '', 'target_groups' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'target_groups', 'action_type_id', 'year', 'month']);
    }

    public function test_update_syncs_groups_and_replaces_resources(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.strategia.store'), $this->payload());
        $plan = StrategyPlan::first();
        $otherGroup = StrategyTargetGroup::skip(1)->first();

        $this->actingAs($admin)
            ->putJson(route('admin.strategia.update', $plan), $this->payload([
                'title'         => 'Po edycji',
                'status'        => 'in_progress',
                'target_groups' => [$otherGroup->id],
                'resources'     => [['type' => 'equipment', 'name' => 'Rzutnik', 'quantity' => 1]],
            ]))
            ->assertOk()
            ->assertJsonPath('plan.title', 'Po edycji')
            ->assertJsonPath('plan.status', 'in_progress');

        $this->assertSame([$otherGroup->id], $plan->fresh()->targetGroups->pluck('id')->all());
        $this->assertDatabaseCount('strategy_plan_resources', 1);
    }

    public function test_list_filters_and_summarizes(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.strategia.store'), $this->payload());
        $this->actingAs($admin)->postJson(route('admin.strategia.store'), $this->payload([
            'title' => 'Kampania jesienna', 'month' => 10, 'budget_planned' => 1000, 'status' => 'done',
        ]));

        // Bez filtrów: dwa plany, budżet zsumowany, rozbicie na 12 miesięcy.
        $this->actingAs($admin)
            ->getJson(route('admin.strategia.list', ['year' => 2026]))
            ->assertOk()
            ->assertJsonPath('summary.total_count', 2)
            ->assertJsonPath('summary.total_budget', 5500)
            ->assertJsonCount(12, 'summary.per_month');

        // Filtr po statusie.
        $this->actingAs($admin)
            ->getJson(route('admin.strategia.list', ['year' => 2026, 'status' => 'done']))
            ->assertJsonPath('summary.total_count', 1)
            ->assertJsonPath('plans.0.title', 'Kampania jesienna');

        // Wyszukiwanie pełnotekstowe po tytule.
        $this->actingAs($admin)
            ->getJson(route('admin.strategia.list', ['year' => 2026, 'q' => 'cyfrowe']))
            ->assertJsonPath('summary.total_count', 1);
    }

    public function test_destroy_removes_plan_with_pivot_and_resources(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.strategia.store'), $this->payload());
        $plan = StrategyPlan::first();

        $this->actingAs($admin)
            ->deleteJson(route('admin.strategia.destroy', $plan))
            ->assertOk();

        $this->assertDatabaseCount('strategy_plans', 0);
        $this->assertDatabaseCount('strategy_plan_resources', 0);
        $this->assertDatabaseCount('strategy_plan_target_group', 0);
    }

    public function test_dictionary_crud_and_delete_guard(): void
    {
        $admin = $this->admin();

        // Dodanie nowej grupy docelowej.
        $response = $this->actingAs($admin)
            ->postJson(route('admin.strategia.slowniki.store', 'grupy'), ['name' => 'Dzieci'])
            ->assertCreated();
        $newId = $response->json('item.id');

        // Walidacja pustej nazwy → 422 JSON.
        $this->actingAs($admin)
            ->postJson(route('admin.strategia.slowniki.store', 'grupy'), ['name' => ''])
            ->assertStatus(422);

        // Pozycja użyta w planie nie daje się usunąć (409), nieużywana — tak.
        $usedId = StrategyTargetGroup::first()->id;
        $this->actingAs($admin)->postJson(route('admin.strategia.store'), $this->payload(['target_groups' => [$usedId]]));

        $this->actingAs($admin)
            ->deleteJson(route('admin.strategia.slowniki.destroy', ['grupy', $usedId]))
            ->assertStatus(409);

        $this->actingAs($admin)
            ->deleteJson(route('admin.strategia.slowniki.destroy', ['grupy', $newId]))
            ->assertOk();
    }

    public function test_dictionaries_require_admin_role(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $this->actingAs($editor)
            ->postJson(route('admin.strategia.slowniki.store', 'grupy'), ['name' => 'X'])
            ->assertForbidden();
    }
}
