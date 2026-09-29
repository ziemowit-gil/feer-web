<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateVmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettingsCache();
    }

    private function resetSettingsCache(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function useVm(array $extra = []): void
    {
        SiteSetting::current()->forceFill(['site_template' => 'vm'] + $extra)->save();
        $this->resetSettingsCache();
    }

    public function test_vm_template_is_offered_in_settings(): void
    {
        $this->assertArrayHasKey('vm', SiteSetting::SITE_TEMPLATES);
    }

    public function test_home_renders_vm_layout_with_four_news(): void
    {
        $this->useVm(['krs_number' => '0000136590']);

        foreach (range(1, 5) as $i) {
            News::create([
                'title' => "Wpis $i",
                'slug' => "wpis-$i",
                'is_published' => true,
                'published_at' => now()->subDays($i),
            ]);
        }

        $this->get('/')->assertOk()
            ->assertSee('template-vm', false)
            ->assertSee('KRS:')
            ->assertSee('0000136590')
            ->assertSee('id="vm-intro-heading"', false)
            ->assertSee('Wpis 4')
            ->assertDontSee('Wpis 5');
    }

    public function test_knowledge_section_lists_children_of_selected_page(): void
    {
        $parent = Page::create(['title' => 'Strefa wiedzy', 'slug' => 'strefa-wiedzy', 'content' => '', 'is_published' => true]);
        Page::create(['title' => 'Pies przewodnik', 'slug' => 'pies-przewodnik', 'content' => '', 'is_published' => true, 'parent_id' => $parent->id]);
        Page::create(['title' => 'Szkic ukryty', 'slug' => 'szkic-ukryty', 'content' => '', 'is_published' => false, 'parent_id' => $parent->id]);

        $this->useVm(['vm_knowledge_page_id' => $parent->id]);

        $this->get('/')->assertOk()
            ->assertSee('id="vm-knowledge-heading"', false)
            ->assertSee('Strefa wiedzy')
            ->assertSee('Pies przewodnik')
            ->assertDontSee('Szkic ukryty');
    }

    public function test_template_tab_has_vm_section_and_intro_is_inline_editable(): void
    {
        $this->useVm();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('x-show="tpl === \'vm\'"', $html);

        $this->actingAs($admin)->put(route('admin.inline-edit.update'), [
            'model' => 'site_setting', 'id' => 1, 'field' => 'vm_intro_heading', 'value' => 'Witamy',
        ])->assertOk();

        $this->resetSettingsCache();
        $this->assertSame('Witamy', SiteSetting::current()->vmIntroHeading());
    }
}
