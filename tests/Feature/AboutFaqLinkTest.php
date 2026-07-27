<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutFaqLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_about_page_shows_faq_link_to_faq_url_when_enabled(): void
    {
        Page::create([
            'title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true,
            'about_faq_visible' => true,
            'about_section_order' => ['faq'],
        ]);

        $this->get('/o-nas')
            ->assertOk()
            ->assertSee('Masz pytania?')
            ->assertSee(url('/faq'), false);
    }

    public function test_no_faq_link_when_disabled(): void
    {
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true, 'about_faq_visible' => false]);

        $this->get('/o-nas')->assertOk()->assertDontSee('Masz pytania?');
    }
}
