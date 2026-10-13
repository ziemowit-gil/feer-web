<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_form_has_content_column_and_publication_panel(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.wydarzenia.create'))->assertOk()->getContent();

        foreach (['data-pe-section="korzysci"', 'data-pe-section="faq"', 'aria-label="Publikacja wydarzenia"', 'name="draft" value="1"', 'Dostępność treści'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        // Pola publikacji leżą w formularzu wydarzenia.
        $form = substr($html, strpos($html, 'id="event-form"'), strpos($html, '</form>', strpos($html, 'id="event-form"')) - strpos($html, 'id="event-form"'));
        $this->assertStringContainsString('name="is_published"', $form);
    }
}
