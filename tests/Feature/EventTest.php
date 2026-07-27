<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NavItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
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

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Szkolenie z dostępności cyfrowej (WCAG)',
            'slug' => 'szkolenie-wcag',
            'lead' => 'Praktyczne wprowadzenie do WCAG dla zespołów NGO.',
            'type' => 'szkolenie',
            'mode' => 'stacjonarnie',
            'location' => 'Nowy Sącz, ul. Barbackiego 28',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
            'registration_url' => 'https://forms.example.com/wcag',
            'is_published' => true,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Szkolenie z dostępności cyfrowej (WCAG)',
            'lead' => 'Praktyczne wprowadzenie do WCAG dla zespołów NGO.',
            'type' => 'szkolenie',
            'mode' => 'stacjonarnie',
            'location' => 'Nowy Sącz, ul. Barbackiego 28',
            'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addWeek()->addHours(3)->format('Y-m-d\TH:i'),
            'registration_url' => 'https://forms.example.com/wcag',
            'audience' => 'brand',
            'is_published' => '1',
        ], $overrides);
    }

    public function test_public_list_and_detail_render(): void
    {
        $event = $this->event();

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('Nadchodzące szkolenia i wydarzenia')
            ->assertSee('Szkolenie z dostępności cyfrowej (WCAG)');

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Zapisy i kontakt')
            ->assertSee('Szkolenie z dostępności cyfrowej (WCAG)');
    }

    public function test_unpublished_event_is_not_publicly_visible(): void
    {
        $event = $this->event(['is_published' => false]);

        $this->get(route('events.show', $event))->assertNotFound();
    }

    public function test_past_event_is_hidden_from_listing_but_reachable_directly(): void
    {
        $event = $this->event([
            'slug' => 'minione',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(10)->addHours(2),
        ]);

        $this->get(route('events.index'))->assertOk()->assertDontSee('szkolenie-wcag');

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('To wydarzenie już się odbyło.');
    }

    public function test_admin_can_create_event(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wydarzenia.store'), $this->payload())
            ->assertRedirect(route('admin.wydarzenia.index'));

        $event = Event::firstWhere('title', 'Szkolenie z dostępności cyfrowej (WCAG)');

        $this->assertNotNull($event);
        $this->assertTrue($event->is_published);
        $this->assertSame('szkolenie-z-dostepnosci-cyfrowej-wcag', $event->slug);
    }

    public function test_location_required_unless_remote(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wydarzenia.store'), $this->payload(['location' => '']))
            ->assertSessionHasErrors('location');

        $this->actingAs($this->admin())
            ->post(route('admin.wydarzenia.store'), $this->payload(['mode' => 'zdalnie', 'location' => '']))
            ->assertRedirect(route('admin.wydarzenia.index'));
    }

    public function test_end_before_start_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wydarzenia.store'), $this->payload([
                'ends_at' => now()->addWeek()->subHour()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_admin_can_convert_event_to_news_draft(): void
    {
        $event = $this->event(['facilitator_name' => 'Anna Kowalska', 'facilitator_role' => 'trenerka']);

        $this->actingAs($this->admin())
            ->post(route('admin.wydarzenia.na-aktualnosc', $event))
            ->assertRedirect();

        $news = \App\Models\News::firstWhere('slug', 'szkolenie-z-dostepnosci-cyfrowej-wcag');

        $this->assertNotNull($news);
        $this->assertFalse($news->is_published);
        $this->assertStringContainsString('Anna Kowalska', $news->content);
        $this->assertStringContainsString('Termin:', $news->content);
    }

    public function test_admin_can_add_faqs_and_they_render_on_the_event_page(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wydarzenia.store'), $this->payload([
                'faqs' => [
                    ['question' => 'Czy otrzymam zaświadczenie?', 'answer' => 'Tak, imienne.'],
                    ['question' => '', 'answer' => ''], // pusty wiersz — pomijany
                ],
            ]))
            ->assertRedirect(route('admin.wydarzenia.index'));

        $event = Event::firstWhere('title', 'Szkolenie z dostępności cyfrowej (WCAG)');

        $this->assertSame(1, $event->faqs()->count());

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Najczęstsze pytania')
            ->assertSee('Czy otrzymam zaświadczenie?');
    }

    public function test_events_nav_type_points_to_the_listing(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pozycje-menu.store'), [
                'label' => 'Szkolenia',
                'type' => 'events',
                'location' => 'main',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item = NavItem::firstWhere('label', 'Szkolenia');

        $this->assertNotNull($item);
        $this->assertSame(url('/wydarzenia'), $item->url);
        $this->assertSame('events', $item->module);
    }
}
