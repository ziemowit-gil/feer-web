<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqHtmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_faq_answer_renders_html_from_wysiwyg(): void
    {
        Page::create([
            'title' => 'Pytania', 'slug' => 'pytania', 'type' => 'faq', 'is_published' => true,
            'faq_items' => [
                ['question' => 'Czy to działa?', 'answer' => '<p>Tak, z <strong>pogrubieniem</strong> i <a href="/kontakt">linkiem</a>.</p>'],
            ],
        ]);

        $this->get('/pytania')
            ->assertOk()
            ->assertSee('Czy to działa?')
            ->assertSee('<strong>pogrubieniem</strong>', false)   // HTML nieescapowany
            ->assertSee('href="/kontakt"', false);
    }
}
