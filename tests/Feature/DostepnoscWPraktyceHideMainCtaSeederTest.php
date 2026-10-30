<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Database\Seeders\DostepnoscWPraktyceHideMainCtaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DostepnoscWPraktyceHideMainCtaSeederTest extends TestCase
{
    use RefreshDatabase;

    private function project(?array $cta): Project
    {
        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);

        return Project::withoutGlobalScopes()->updateOrCreate(['slug' => 'dostepnosc-w-praktyce'], [
            'title' => 'Dostępność w praktyce', 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true, 'main_cta' => $cta,
        ]);
    }

    public function test_wylacza_przycisk_napisz_do_nas_i_jest_idempotentny(): void
    {
        $project = $this->project(['label' => 'Napisz do nas', 'url' => '/kontakt']);

        $this->seed(DostepnoscWPraktyceHideMainCtaSeeder::class);
        $this->seed(DostepnoscWPraktyceHideMainCtaSeeder::class);

        $this->assertNull($project->fresh()->main_cta);
    }

    public function test_nie_nadpisuje_wlasnego_przycisku_z_panelu(): void
    {
        $project = $this->project(['label' => 'Zgłoś się', 'url' => '/formularz/wspolpraca']);

        $this->seed(DostepnoscWPraktyceHideMainCtaSeeder::class);

        $this->assertSame('Zgłoś się', $project->fresh()->main_cta['label']);
    }

    public function test_strona_dzialania_bez_glownego_przycisku_nie_pokazuje_go(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['projects_extras_enabled' => true]);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $project = $this->project(['label' => 'Napisz do nas', 'url' => '/kontakt']);
        $before = $this->get(route('projects.show', $project))->assertOk()->getContent();

        $this->seed(DostepnoscWPraktyceHideMainCtaSeeder::class);
        $after = $this->get(route('projects.show', $project->fresh()))->assertOk()->getContent();

        $this->assertStringContainsString('Napisz do nas', $before, 'przed zmianą przycisk jest');
        $this->assertStringNotContainsString('Napisz do nas', $after, 'po zmianie przycisku nie ma');
    }
}
