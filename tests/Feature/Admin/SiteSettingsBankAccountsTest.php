<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsBankAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SiteSetting::current() caches its singleton in a static property that
        // survives RefreshDatabase's per-test rollback. Reset it so a stale model
        // pointing at a rolled-back row can't leak between tests. (In production
        // each request is its own process, so this only matters in the suite.)
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /**
     * A valid full settings-form payload; individual tests override the bits
     * they care about. Mirrors the required fields in SiteSettingController.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'FEER',
            'brand_color' => '#c31432',
            'header_layout' => 'classic',
            'content_editor' => 'tinymce',
            'mail_transport' => 'default',
            'contact_address' => 'Władysława Barbackiego 28/18',
            'contact_city' => '33-300 Nowy Sącz',
            'contact_email' => 'fundacja@feer.org.pl',
        ], $overrides);
    }

    public function test_settings_sidebar_shows_a_submenu_linking_to_each_tab(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'));

        $response->assertOk();
        // Each settings tab is deep-linked from the sidebar sub-menu.
        foreach (array_keys(SiteSetting::SETTINGS_TABS) as $tab) {
            $response->assertSee('tab='.$tab);
        }
        $response->assertSee('Dane rejestrowe');
        $response->assertSee('Wesprzyj nas');
    }

    public function test_bank_accounts_are_saved_and_empty_rows_are_dropped(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->payload([
                'contact_bank_accounts' => [
                    ['number' => 'PL61 1090 1014 0000 0712 1981 2874', 'purpose' => 'Darowizny statutowe'],
                    ['number' => '', 'purpose' => 'wiersz bez numeru — do odrzucenia'],
                    ['number' => '  PL27 1090 1014 0000 0712 1981 3001  ', 'purpose' => '  Projekt Wiem FEER  '],
                ],
            ]))
            ->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'general']));

        $accounts = SiteSetting::query()->first()->contact_bank_accounts;

        $this->assertCount(2, $accounts);
        $this->assertSame('PL61 1090 1014 0000 0712 1981 2874', $accounts[0]['number']);
        $this->assertSame('Darowizny statutowe', $accounts[0]['purpose']);
        // Values are trimmed and the list is re-indexed after dropping the empty row.
        $this->assertSame('PL27 1090 1014 0000 0712 1981 3001', $accounts[1]['number']);
        $this->assertSame('Projekt Wiem FEER', $accounts[1]['purpose']);
    }

    public function test_contact_page_lists_the_bank_accounts(): void
    {
        $settings = SiteSetting::current();
        $settings->update([
            'contact_bank_accounts' => [
                ['number' => 'PL61 1090 1014 0000 0712 1981 2874', 'purpose' => 'Darowizny na cele statutowe'],
            ],
        ]);

        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertSee('Numery rachunków bankowych');
        $response->assertSee('PL61 1090 1014 0000 0712 1981 2874');
        $response->assertSee('Darowizny na cele statutowe');
    }
}
