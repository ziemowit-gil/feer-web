<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ramka „Możesz też wybrać formę płatną”: wyjaśnia, że część kosztów pokrywa Beneficjent, i dlaczego tak jest. */
class ProjectPaidFormExplanationTest extends TestCase
{
    use RefreshDatabase;

    private function parentWithChildren(array $children): Project
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['projects_subprojects_enabled' => true]);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);
        $parent = Project::create(['title' => 'Szkolenia testowe', 'slug' => 'szkolenia-testowe', 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true]);

        foreach ($children as $i => $paid) {
            Project::create(['title' => 'Forma '.$i, 'slug' => 'forma-'.$i, 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true,
                'is_paid' => $paid, 'is_offered' => true, 'parent_id' => $parent->id]);
        }

        return $parent;
    }

    public function test_jedna_forma_platna_tlumaczy_kto_pokrywa_koszty_i_dlaczego(): void
    {
        $html = $this->get(route('projects.show', $this->parentWithChildren([true])))->assertOk()->getContent();

        $this->assertStringContainsString('Możesz też wybrać formę płatną', $html);
        $this->assertStringContainsString('część kosztów pokrywa <strong>Beneficjent</strong>', $html);
        $this->assertStringContainsString('Dzieje się tak, bo na to działanie nie mamy dość środków', $html);
        $this->assertStringNotContainsString('Wtedy sam ustalasz termin i zakres', $html);
    }

    public function test_forma_bezplatna_i_platna_razem_tez_wyjasnia_forme_platna(): void
    {
        $html = $this->get(route('projects.show', $this->parentWithChildren([false, true])))->assertOk()->getContent();

        $this->assertStringContainsString('Jeden jest <strong>bezpłatny</strong>', $html);
        $this->assertStringContainsString('część kosztów pokrywa <strong>Beneficjent</strong>', $html);
        $this->assertStringContainsString('Płatna forma istnieje, bo na nią nie mamy dość środków', $html);
    }
}
