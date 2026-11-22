<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MainSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zmiana witryny głównej (MainSite, SiteSetting::main()): panel Witryny → „Ustaw jako główną” i witryny:glowna.
 */
class MainSiteSwitchTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetCurrent();
        $this->main = SiteSetting::current();
        $this->sub = $this->site('Kreatywni', 'kreatywni');
    }

    private function forgetCurrent(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function site(string $name, string $slug, array $extra = []): SiteSetting
    {
        return SiteSetting::create([
            'site_name' => $name, 'slug' => $slug, 'parent_site_id' => $this->main->id,
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ] + $extra);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_main_is_site_one_until_switched(): void
    {
        $this->assertTrue(SiteSetting::main()->is($this->main));
        $this->assertSame(1, (int) SiteSetting::main()->id);
    }

    public function test_switch_makes_site_main_and_previous_its_subsite(): void
    {
        $nested = $this->site('Ośrodek', 'osrodek');

        $previous = MainSite::switchTo($this->sub);
        $this->forgetCurrent();

        $this->assertTrue($previous->is($this->main));
        $this->assertTrue(SiteSetting::main()->is($this->sub));
        $this->assertTrue(SiteSetting::current()->is($this->sub));
        $this->assertNull($this->sub->fresh()->parent_site_id);
        $this->assertSame($this->sub->id, (int) $this->main->fresh()->parent_site_id);
        // Pozostałe subwitryny dawnej głównej zostają przy niej, adres skrócony nowej głównej bez zmian.
        $this->assertSame($this->main->id, (int) $nested->fresh()->parent_site_id);
        $this->assertSame('kreatywni', $this->sub->fresh()->slug);

        // I z powrotem.
        MainSite::switchTo($this->main->fresh());
        $this->forgetCurrent();
        $this->assertTrue(SiteSetting::current()->is($this->main));
        $this->assertSame($this->main->id, (int) $this->sub->fresh()->parent_site_id);
    }

    public function test_root_address_serves_new_main_site(): void
    {
        Page::create(['site_id' => $this->sub->id, 'title' => 'Oferta warsztatów', 'slug' => 'oferta-warsztatow', 'content' => '<p>x</p>', 'is_published' => true]);

        MainSite::switchTo($this->sub);
        $this->forgetCurrent();

        $this->get('/oferta-warsztatow')->assertOk()->assertSee('Oferta warsztatów');
    }

    public function test_refuses_bip_trashed_and_current_main(): void
    {
        $bip = $this->site('BIP', 'bip-test', ['site_kind' => 'bip']);

        $this->assertNotNull(MainSite::refusal($bip));
        $this->assertNotNull(MainSite::refusal($this->main));
        $this->assertNull(MainSite::refusal($this->sub));

        $this->expectException(\InvalidArgumentException::class);
        MainSite::switchTo($bip);
    }

    public function test_panel_confirm_and_switch(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.witryny.index'))->assertOk()
            ->assertSee(route('admin.witryny.glowna', $this->sub), false)
            ->assertSee('Ustaw jako główną');

        $this->actingAs($admin)->get(route('admin.witryny.glowna', $this->sub))->assertOk()
            ->assertSee('Ustaw „Kreatywni” jako witrynę główną', false)
            ->assertSee('stanie się subwitryną')
            ->assertSee('Ustawienia całej instalacji');

        $this->actingAs($admin)->post(route('admin.witryny.glowna.update', $this->sub))
            ->assertRedirect(route('admin.witryny.index'))
            ->assertSessionHas('status');

        $this->forgetCurrent();
        $this->assertTrue(SiteSetting::main()->is($this->sub));
    }

    public function test_panel_refuses_bip_and_non_admin(): void
    {
        $bip = $this->site('BIP', 'bip-test', ['site_kind' => 'bip']);

        $this->actingAs($this->admin())->post(route('admin.witryny.glowna.update', $bip))
            ->assertRedirect(route('admin.witryny.index'))->assertSessionHas('error');

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->post(route('admin.witryny.glowna.update', $this->sub))->assertForbidden();

        $this->forgetCurrent();
        $this->assertTrue(SiteSetting::main()->is($this->main));
    }

    public function test_command_lists_and_switches_by_slug(): void
    {
        $this->artisan('witryny:glowna')->expectsOutputToContain('Witryna główna')->assertSuccessful();
        $this->artisan('witryny:glowna', ['witryna' => 'nie-ma-takiej'])->assertFailed();

        $this->artisan('witryny:glowna', ['witryna' => 'KREATYWNI'])
            ->expectsOutputToContain('Witryną główną jest teraz „Kreatywni”')
            ->assertSuccessful();

        $this->forgetCurrent();
        $this->assertTrue(SiteSetting::main()->is($this->sub));

        $this->artisan('witryny:glowna', ['witryna' => (string) $this->sub->id])->assertFailed(); // już główna
    }
}
