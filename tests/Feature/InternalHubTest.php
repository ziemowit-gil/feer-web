<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_hub_is_access_restricted_and_renders_link_tiles_when_unlocked(): void
    {
        $page = Page::create([
            'title' => 'Panel współpracownika', 'slug' => 'panel', 'type' => 'internal_hub',
            'is_published' => true, 'access_mode' => 'password', 'access_password' => Hash::make('haslo'),
            'hub_intro' => 'Systemy dla współpracowników',
            'hub_links' => [
                ['label' => 'Poczta', 'url' => 'https://mail.example.com', 'description' => 'Skrzynka', 'icon' => 'fa-solid fa-envelope'],
            ],
        ]);

        // Bez odblokowania — bramka, brak linków.
        $this->get('/panel')->assertStatus(403)->assertDontSee('Poczta');

        // Po odblokowaniu — hero + kafelki linków.
        $this->post(route('page.unlock', $page), ['access_password' => 'haslo'])->assertRedirect();
        $this->get('/panel')
            ->assertOk()
            ->assertSee('Panel współpracownika')
            ->assertSee('Systemy dla współpracowników')
            ->assertSee('Poczta')
            ->assertSee('https://mail.example.com');
    }

    public function test_admin_can_save_hub_page_with_links(): void
    {
        $page = Page::create(['title' => 'Panel', 'slug' => 'panel', 'type' => 'internal_hub', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.podstrony.update', $page), [
                'title' => 'Panel', 'slug' => 'panel', 'type' => 'internal_hub', 'is_published' => '1',
                'parent_id' => '', 'project_id' => '',
                'access_mode' => 'microsoft',
                'hub_hero' => 'https://example.com/hero.jpg',
                'hub_intro' => 'Wstęp',
                'hub_links' => [
                    ['label' => 'CRM', 'url' => 'https://crm.example.com', 'description' => '', 'icon' => ''],
                    ['label' => '', 'url' => '', 'description' => '', 'icon' => ''], // pusty — pomijany
                ],
            ])
            ->assertRedirect(route('admin.podstrony.index'));

        $fresh = $page->fresh();
        $this->assertSame('microsoft', $fresh->access_mode);
        $this->assertSame('https://example.com/hero.jpg', $fresh->hub_hero);
        $this->assertCount(1, $fresh->hub_links);
        $this->assertSame('CRM', $fresh->hub_links[0]['label']);
    }
}
