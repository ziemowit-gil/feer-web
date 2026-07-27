<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SideNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function parentWithChild(bool $sideNav): Page
    {
        $parent = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true, 'show_side_nav' => $sideNav]);
        Page::create(['title' => 'Podstrona A', 'slug' => 'podstrona-a', 'type' => 'standard', 'is_published' => true, 'parent_id' => $parent->id, 'show_side_nav' => $sideNav]);

        return $parent;
    }

    public function test_side_nav_shows_children_when_enabled(): void
    {
        $this->parentWithChild(true);

        $this->get('/dzial')
            ->assertOk()
            ->assertSee('Podstrony w tym dziale')
            ->assertSee('Podstrona A');
    }

    public function test_side_nav_hidden_when_disabled(): void
    {
        $this->parentWithChild(false);

        $this->get('/dzial')
            ->assertOk()
            ->assertDontSee('Podstrony w tym dziale');
    }
}
