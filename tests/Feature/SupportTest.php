<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_support_page_renders_with_wplacam_and_stats(): void
    {
        SiteSetting::current()->update([
            'support_wplacam_url' => 'https://wplacam.ngo.pl/wesprzyj/feer',
        ]);

        Page::create([
            'title' => 'O nas',
            'slug' => 'o-nas',
            'type' => 'about',
            'about_stats' => [
                ['value' => '1200+', 'label' => 'przeszkolonych osób'],
                ['value' => '8 lat', 'label' => 'działalności'],
            ],
        ]);

        $this->get(route('support.show'))
            ->assertOk()
            ->assertSee('wpłacam.ngo.pl')
            ->assertSee('1200+')
            ->assertSee('przeszkolonych osób');
    }

    public function test_support_page_renders_without_optional_data(): void
    {
        $this->get(route('support.show'))->assertOk();
    }

    public function test_fundraiser_and_testimonial_render_when_set(): void
    {
        SiteSetting::current()->update([
            'support_fundraiser_title' => 'Pracownia cyfrowa',
            'support_fundraiser_goal' => 20000,
            'support_fundraiser_raised' => 5000,
            'support_testimonial_quote' => 'Dzięki FEER odzyskałam samodzielność.',
            'support_testimonial_author' => 'Anna',
        ]);

        $this->get(route('support.show'))
            ->assertOk()
            ->assertSee('Pracownia cyfrowa')
            ->assertSee('25%')                 // 5000 / 20000
            ->assertSee('Dzięki FEER odzyskałam samodzielność.')
            ->assertSee('Anna');
    }
}
