<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function page(): void
    {
        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
    }

    public function test_cms_credit_shown_by_default(): void
    {
        $this->page();

        $this->get('/start')->assertOk()->assertSee('weCMS');
    }

    public function test_cms_credit_hidden_when_disabled(): void
    {
        $this->page();
        SiteSetting::current()->update(['show_cms_credit' => false]);

        $this->get('/start')->assertOk()->assertDontSee('weCMS');
    }
}
