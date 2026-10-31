<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ekran wyboru witryny po logowaniu i kopiowanie ustawień nagłówka między witrynami. */
class SiteChooserTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_pulpit_kieruje_na_wybor_witryny_raz_na_sesje(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertRedirect(route('admin.wybor-witryny'));

        $this->get(route('admin.wybor-witryny'))->assertOk()->assertSee('Nad którą witryną chcesz dziś pracować?')->assertSee('Szkolenia');

        $this->post(route('admin.wybor-witryny.choose', $this->sub))->assertRedirect(route('admin.dashboard'));
        $this->assertSame($this->sub->id, session('admin_active_site_id'));

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_bez_dodatkowych_witryn_nie_ma_ekranu_wyboru(): void
    {
        $this->sub->delete();

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();
    }

    public function test_przenosi_wybrane_grupy_ustawien_do_wybranych_witryn(): void
    {
        $this->main->forceFill(['header_layout' => 'brand_bar', 'show_topbar_bip' => false, 'wide_mission_cta_label' => 'Wesprzyj'])->save();
        $this->sub->forceFill(['header_layout' => 'classic', 'brand_color' => '#123456'])->save();

        $this->actingAs($this->admin())->post(route('admin.witryny.ustawienia.copy'), [
            'source_site_id' => $this->main->id, 'target_site_ids' => [$this->sub->id], 'groups' => ['header'],
        ])->assertRedirect(route('admin.witryny.ustawienia'))->assertSessionHas('status');

        $sub = $this->sub->fresh();
        $this->assertSame('brand_bar', $sub->header_layout);
        $this->assertFalse((bool) $sub->show_topbar_bip);
        $this->assertSame('Wesprzyj', $sub->wide_mission_cta_label);
        $this->assertSame('#123456', $sub->brand_color, 'kolory marki zostają bez zmian');

        $this->get(route('admin.witryny.ustawienia'))->assertOk()->assertSee('Przenieś ustawienia');
    }

    public function test_kopiowanie_naglowka_wymaga_witryn_docelowych_i_admina(): void
    {
        $this->actingAs($this->admin())->post(route('admin.witryny.ustawienia.copy'), ['source_site_id' => $this->main->id, 'groups' => ['header']])
            ->assertSessionHasErrors('target_site_ids');

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->get(route('admin.witryny.ustawienia'))->assertForbidden();
    }
}
