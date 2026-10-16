<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Szablon FEER: pod kaflami „Nasze działania” zachęta do zapisu na newsletter (link do strony zapisu). */
class FeerProjectsNewsletterCtaTest extends TestCase
{
    use RefreshDatabase;

    private function home(bool $newsletter): string
    {
        $manager = app(ModuleManager::class);
        \Closure::bind(function () use ($newsletter) {
            $this->statuses->put('newsletter', $newsletter ? 'active' : 'inactive');
        }, $manager, ModuleManager::class)();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        return $this->get('/')->assertOk()->getContent();
    }

    private function project(): void
    {
        $category = Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia']);
        Project::create(['category_id' => $category->id, 'title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'excerpt' => 'Pomagamy', 'is_published' => true, 'show_on_home' => true]);
    }

    public function test_pod_kaflami_dzialan_jest_zacheta_do_newslettera(): void
    {
        $this->project();
        $html = $this->home(true);

        $this->assertStringContainsString('Nie przegap nowych szkoleń i materiałów', $html);
        $this->assertStringContainsString('href="'.route('newsletter.show').'"', $html);
        $this->assertStringContainsString('Zapisz się na newsletter', $html);
        // Zaraz po liście kafli, przed kolejną sekcją.
        $this->assertGreaterThan(strpos($html, 'Wsparcie IT'), strpos($html, 'Nie przegap nowych szkoleń'));
    }

    public function test_bez_modulu_newsletter_nie_ma_zachety(): void
    {
        $this->project();

        $this->assertStringNotContainsString('Nie przegap nowych szkoleń i materiałów', $this->home(false));
    }
}
