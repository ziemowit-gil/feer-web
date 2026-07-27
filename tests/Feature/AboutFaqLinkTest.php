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

    public function test_about_page_shows_faq_link_when_set(): void
    {
        $faq = Page::create(['title' => 'Najczęstsze pytania', 'slug' => 'faq', 'type' => 'faq', 'is_published' => true]);
        Page::create([
            'title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true,
            'about_faq_page_id' => $faq->id,
            'about_section_order' => ['faq'],
        ]);

        $this->get('/o-nas')
            ->assertOk()
            ->assertSee('Masz pytania?')
            ->assertSee('Najczęstsze pytania')
            ->assertSee(route('page.show', $faq), false);
    }

    public function test_no_faq_link_when_not_set(): void
    {
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true]);

        $this->get('/o-nas')->assertOk()->assertDontSee('Masz pytania?');
    }
}
