<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dziennik zdarzeń jako oś czasu pogrupowana po dniach: godzina, kto, rodzaj zdarzenia i treść. */
class ActivityTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_dziennik_pokazuje_dzisiejsze_zdarzenia_na_osi_czasu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Anna Redaktorka']);
        $this->actingAs($admin);
        News::create(['title' => 'Nowa aktualność o szkoleniu', 'slug' => 'nowa-o-szkoleniu', 'content' => '<p>x</p>', 'is_published' => false]);

        $html = $this->get(route('admin.dziennik.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<h2 id="day-'.now()->format('Y-m-d').'"[^>]*>\s*<span>Dziś</span>~u', $html);
        $this->assertStringContainsString('<strong class="font-bold">Anna Redaktorka</strong>', $html);
        $this->assertStringContainsString('„Nowa aktualność o szkoleniu”', $html);
        $this->assertStringContainsString('<span class="font-bold">Utworzenie</span>', $html);
        $this->assertStringContainsString('<label for="f-event" class="mb-1 block text-sm font-bold text-ink">', $html);
    }
}
