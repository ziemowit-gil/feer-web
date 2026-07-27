<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function project(array $overrides = []): Project
    {
        $category = Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia', 'order' => 0]);

        return Project::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Warsztaty WCAG', 'slug' => 'warsztaty-wcag', 'is_published' => true,
        ], $overrides));
    }

    public function test_pricing_shows_on_paid_project(): void
    {
        $this->project([
            'is_paid' => true,
            'pricing' => [
                ['item' => 'Szkolenie 1-dniowe', 'price' => '1200 zł', 'note' => 'grupa do 15 osób'],
            ],
        ]);

        $this->get('/projekty/warsztaty-wcag')
            ->assertOk()
            ->assertSee('Cennik')
            ->assertSee('Szkolenie 1-dniowe')
            ->assertSee('1200 zł')
            ->assertSee('grupa do 15 osób');
    }

    public function test_pricing_hidden_when_not_paid(): void
    {
        $this->project([
            'is_paid' => false,
            'pricing' => [['item' => 'X', 'price' => '10 zł', 'note' => '']],
        ]);

        $this->get('/projekty/warsztaty-wcag')->assertOk()->assertDontSee('Cennik');
    }
}
