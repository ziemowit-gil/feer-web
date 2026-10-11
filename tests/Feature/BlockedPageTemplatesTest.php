<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedPageTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + ['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'parent_id' => '', 'is_published' => '1'];
    }

    public function test_blocked_template_is_hidden_and_rejected_but_current_value_is_kept(): void
    {
        SiteSetting::current()->update(['blocked_options' => ['page_templates' => ['landing']]]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertDontSee('<option value="landing"', false)
            ->assertSee('<option value="section"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), $this->payload(['page_template' => 'landing']))
            ->assertSessionHasErrors('page_template');

        // Strona, która już ma zablokowany szablon, może go zachować (i nadal go widzi w wyborze).
        $page->update(['page_template' => 'landing']);
        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('<option value="landing"', false);
        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), $this->payload(['page_template' => 'landing']))
            ->assertSessionHasNoErrors();
    }
}
