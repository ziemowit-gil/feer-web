<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_faq_editor_offers_link_to_internal_pages(): void
    {
        Page::create(['title' => 'Kontakt do fundacji', 'slug' => 'kontakt-fundacja', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.podstrony.create'))
            ->assertOk()
            ->assertSee('Wstaw link do podstrony')
            ->assertSee('data-faq-page-link', false)
            ->assertSee('Kontakt do fundacji');
    }
}
