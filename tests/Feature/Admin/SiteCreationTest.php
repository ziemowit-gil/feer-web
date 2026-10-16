<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_nowa_witryna_bez_koloru_dziedziczy_kolor_od_nadrzednej(): void
    {
        SiteSetting::current()->update(['brand_color' => '#112233']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->actingAs($this->admin())->post(route('admin.witryny.store'), [
            'site_name' => 'Centrum Szkoleniowe', 'tagline' => 'Szkolenia', 'brand_color' => '', 'slug' => 'centrum', 'domain' => '',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.witryny.index'));

        $site = SiteSetting::where('slug', 'centrum')->firstOrFail();
        $this->assertSame('#112233', $site->brand_color);
        $this->assertNotNull(SiteSetting::current()->id);
    }

    public function test_witryna_z_wlasnym_kolorem_zachowuje_go_a_edycja_z_pustym_polem_go_nie_kasuje(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'Ośrodek', 'brand_color' => '#1d4ed8', 'slug' => 'osrodek']);
        $site = SiteSetting::where('slug', 'osrodek')->firstOrFail();
        $this->assertSame('#1d4ed8', $site->brand_color);

        $this->actingAs($admin)->put(route('admin.witryny.update', $site), ['site_name' => 'Ośrodek 2', 'brand_color' => '', 'slug' => 'osrodek'])
            ->assertSessionHasNoErrors();

        $this->assertSame('#1d4ed8', $site->fresh()->brand_color);
        $this->assertSame('Ośrodek 2', $site->fresh()->site_name);
    }

    public function test_nowa_witryna_moze_dostac_szablon_feer_a_bez_wyboru_dziedziczy(): void
    {
        $admin = $this->admin();
        SiteSetting::current()->update(['site_template' => 'default']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->actingAs($admin)->get(route('admin.witryny.create'))->assertOk()
            ->assertSee('Szablon witryny')->assertSee('<option value="feer"', false);

        $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'Ośrodek FEER', 'slug' => 'osrodek-feer', 'site_template' => 'feer'])
            ->assertSessionHasNoErrors();
        $this->assertSame('feer', SiteSetting::where('slug', 'osrodek-feer')->value('site_template'));

        $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'Ośrodek 2', 'slug' => 'osrodek-2']);
        $site = SiteSetting::where('slug', 'osrodek-2')->firstOrFail();
        $this->assertSame('default', $site->site_template);

        // Zmiana szablonu istniejącej witryny; puste pole go nie kasuje.
        $this->actingAs($admin)->put(route('admin.witryny.update', $site), ['site_name' => 'Ośrodek 2', 'slug' => 'osrodek-2', 'site_template' => 'feer'])->assertSessionHasNoErrors();
        $this->assertSame('feer', $site->fresh()->site_template);
        $this->actingAs($admin)->put(route('admin.witryny.update', $site), ['site_name' => 'Ośrodek 2', 'slug' => 'osrodek-2', 'site_template' => ''])->assertSessionHasNoErrors();
        $this->assertSame('feer', $site->fresh()->site_template);

        // Szablon zablokowany w instalacji jest odrzucany.
        SiteSetting::current()->update(['blocked_options' => ['site_templates' => ['vm']]]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'Ośrodek 3', 'slug' => 'osrodek-3', 'site_template' => 'vm'])
            ->assertSessionHasErrors('site_template');
    }
}
