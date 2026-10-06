<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactSettingsOnPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_strona_kontakt_zapisuje_opcje_w_ustawieniach(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => true]);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'parent_id' => '',
            'contact_email' => 'biuro@example.org', 'contact_phone' => '600 100 200',
            'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny'], ['number' => '', 'purpose' => 'puste']],
            'contact_schedule_enabled' => '1',
        ])->assertSessionHasNoErrors();

        $s = SiteSetting::current()->fresh();
        $this->assertSame('biuro@example.org', $s->contact_email);
        $this->assertSame('600 100 200', $s->contact_phone);
        $this->assertCount(1, $s->contact_bank_accounts);
        $this->assertTrue((bool) $s->contact_schedule_enabled);
    }

    public function test_formularz_strony_kontakt_ma_wszystkie_opcje_a_ustawienia_odsylaja_do_niej(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => true]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('name="contact_bank_accounts_layout"', false)
            ->assertSee('name="contact_office_address"', false)
            ->assertSee('name="office_photo"', false)
            ->assertSee('name="notify_schedule_change"', false);

        $html = $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'contact']))->assertOk()
            ->assertSee('Kontakt zarządzasz teraz na stronie', false)
            ->getContent();
        $this->assertStringNotContainsString('name="contact_email"', $html);

        $this->actingAs($admin)->get(route('admin.podstrony.create', ['type' => 'contact']))->assertOk();
    }
}
