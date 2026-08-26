<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderOfficeBarTest extends TestCase
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
        $settings->forceFill(array_merge([
            'header_layout' => 'office_bar',
            'bank_account_number' => 'PL 11 2222 3333 4444 5555 6666 7777',
            'facebook_url' => 'https://facebook.com/test',
            'linkedin_url' => 'https://linkedin.com/company/test',
            'youtube_url' => 'https://youtube.com/@test',
        ], $attributes))->save();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        return $settings;
    }

    public function test_office_bar_shows_account_search_and_three_socials(): void
    {
        $this->settings();

        $response = $this->get('/kontakt')->assertOk()
            ->assertSee('PL 11 2222 3333 4444 5555 6666 7777')
            ->assertSee('id="office-search"', false)
            ->assertSee('Pasek informacyjny i dostępność', false);

        // Belka pokazuje maksymalnie trzy pierwsze uzupełnione profile.
        $this->assertSame(3, substr_count($response->getContent(), 'otwiera się w nowej karcie') / 2);
    }

    public function test_office_bar_elements_can_be_switched_off(): void
    {
        $this->settings([
            'infobar_show_date' => false,
            'infobar_show_nameday' => false,
            'office_show_account' => false,
            'office_show_search' => false,
        ]);

        $this->get('/kontakt')->assertOk()
            ->assertDontSee('PL 11 2222 3333 4444 5555 6666 7777')
            ->assertDontSee('id="office-search"', false)
            ->assertDontSee('Imieniny:');
    }

    public function test_other_layouts_keep_the_regular_topbar(): void
    {
        $this->settings(['header_layout' => 'classic']);

        $this->get('/kontakt')->assertOk()->assertDontSee('id="office-search"', false);
    }
}
