<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_public_page_shows_published_faqs_grouped_by_category(): void
    {
        Faq::create(['question' => 'Czy szkolenia są płatne?', 'answer' => 'Zwykle nie.', 'category' => 'Szkolenia']);
        Faq::create(['question' => 'Szkic pytanie', 'answer' => 'Ukryte.', 'is_published' => false]);

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertSee('Najczęstsze pytania')
            ->assertSee('Czy szkolenia są płatne?')
            ->assertSee('Szkolenia')
            ->assertDontSee('Szkic pytanie');
    }

    public function test_admin_can_create_faq(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.faq.store'), [
                'question' => 'Jak zostać wolontariuszem?',
                'answer' => 'Wypełnij formularz.',
                'category' => 'Wolontariat',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.faq.index'));

        $this->assertDatabaseHas('faqs', ['question' => 'Jak zostać wolontariuszem?', 'is_published' => true]);
    }

    public function test_faq_nav_type_points_to_the_page(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pozycje-menu.store'), [
                'label' => 'Pytania',
                'type' => 'faq',
                'location' => 'main',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item = \App\Models\NavItem::firstWhere('label', 'Pytania');

        $this->assertNotNull($item);
        $this->assertSame(url('/faq'), $item->url);
        $this->assertSame('faq', $item->module);
    }
}
