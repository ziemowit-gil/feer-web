<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona „dopiero tworzymy” (przełącznik witryny) i tło stron serwisowych. */
class UnderConstructionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
    }

    public function test_site_is_normal_by_default(): void
    {
        $this->get('/start')->assertOk()->assertDontSee('Dopiero tworzymy', false);
    }

    public function test_under_construction_page_with_backdrop_and_default_text(): void
    {
        SiteSetting::current()->update(['under_construction' => true]);

        $this->get('/start')->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertSee('Dopiero tworzymy tę witrynę', false)
            ->assertSee(SiteSetting::DEFAULT_UNDER_CONSTRUCTION_MESSAGE, false)
            ->assertSee('cb-backdrop', false)
            ->assertSee('noindex', false);
    }

    public function test_custom_message_and_maintenance_has_priority(): void
    {
        SiteSetting::current()->update(['under_construction' => true, 'under_construction_message' => 'Wracamy w grudniu.']);
        $this->get('/start')->assertStatus(503)->assertSee('Wracamy w grudniu.', false);

        SiteSetting::current()->update(['maintenance_mode' => true]);
        $this->get('/start')->assertStatus(503)->assertSee('Przerwa techniczna', false)->assertSee('cb-backdrop', false);
    }

    public function test_logged_in_user_sees_the_site(): void
    {
        SiteSetting::current()->update(['under_construction' => true]);

        $this->actingAs(User::factory()->create())->get('/start')->assertOk();
    }
}
