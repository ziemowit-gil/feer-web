<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeaderInstitutionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function settings(array $attributes = []): SiteSetting
    {
        $settings = SiteSetting::current();
        $settings->forceFill(array_merge(['header_layout' => 'institutional', 'show_topbar_bip' => true], $attributes))->save();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        return SiteSetting::current();
    }

    public function test_institutional_header_has_menu_tools_and_no_topbar(): void
    {
        $this->settings();

        $this->get('/kontakt')->assertOk()
            ->assertSee('header-inst-nav', false)
            ->assertSee('aria-controls="inst-search"', false)
            ->assertSee('aria-controls="a11y-panel"', false)
            ->assertSee('Biuletyn Informacji Publicznej')
            ->assertDontSee('site-topbar', false);
    }

    public function test_partner_logos_are_shown_with_alt_text(): void
    {
        Storage::fake('public');
        $settings = $this->settings();
        $settings->addMedia(UploadedFile::fake()->image('ue.png', 200, 60))
            ->usingName('Dofinansowane przez Unię Europejską')
            ->toMediaCollection('header_partners');

        $this->get('/kontakt')->assertOk()
            ->assertSee('aria-label="Partnerzy i źródła finansowania"', false)
            ->assertSee('alt="Dofinansowane przez Unię Europejską"', false);
    }

    public function test_other_layouts_keep_the_topbar(): void
    {
        $this->settings(['header_layout' => 'classic']);

        $this->get('/kontakt')->assertOk()
            ->assertSee('site-topbar', false)
            ->assertDontSee('header-inst-nav', false);
    }
}
