<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FAQ działania: osobna sekcja w panelu (niezależna od „Dodatkowych informacji”) i lista pytań na stronie działania
 * z danymi FAQPage — zawsze, gdy FAQ jest wypełnione, także dla „działalności fundacji”.
 */
class ProjectFaqAlwaysTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    public function test_faq_dzialania_bez_dodatkowych_informacji(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['projects_extras_enabled' => false])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $cat = Category::create(['name' => 'Warsztaty', 'slug' => 'warsztaty']);
        $project = Project::create(['category_id' => $cat->id, 'title' => 'Warsztaty w szkołach', 'slug' => 'warsztaty-w-szkolach', 'is_published' => true,
            'kind' => 'foundation_activity', 'content' => '<p>Opis.</p>',
            'faq' => [['q' => 'Dla kogo są zajęcia?', 'a' => 'Dla klas 1–8.']]]);

        $page = $this->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertStringContainsString('Dla kogo są zajęcia?', $page);
        $this->assertStringContainsString('Dla klas 1–8.', $page);
        $this->assertStringContainsString('"@type":"FAQPage"', $page);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->get(route('admin.projekty.edit', $project))->assertOk()
            ->assertSee('data-pe-section="faq"', false)->assertSee('Dodaj pytanie');
    }
}
