<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Witryny sieci: usuwanie przez stronę potwierdzenia, wymuszone usunięcie razem z treścią. */
class SiteForceDeleteTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Ośrodek Testowy', 'slug' => 'osrodek-testowy', 'parent_site_id' => $this->main->id,
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function content(): void
    {
        Page::create(['site_id' => $this->sub->id, 'title' => 'Strona ośrodka', 'slug' => 'strona-osrodka', 'content' => '<p>x</p>', 'is_published' => true]);
        News::create(['site_id' => $this->sub->id, 'title' => 'News ośrodka', 'slug' => 'news-osrodka', 'content' => '<p>x</p>', 'is_published' => true]);
        Page::create(['site_id' => $this->main->id, 'title' => 'Główna', 'slug' => 'glowna-strona', 'content' => '<p>x</p>', 'is_published' => true]);
    }

    public function test_strona_potwierdzenia_pokazuje_tresc_i_pola(): void
    {
        $this->content();

        $this->actingAs($this->admin())->get(route('admin.witryny.confirm-destroy', $this->sub))->assertOk()
            ->assertSee('Usuń witrynę „Ośrodek Testowy”', false)
            ->assertSee('Witryna ma treść')
            ->assertSee('<label for="confirm-name"', false)
            ->assertSee('Usuń witrynę i całą treść');

        $this->actingAs($this->admin())->get(route('admin.witryny.index'))->assertOk()
            ->assertSee(route('admin.witryny.confirm-destroy', $this->sub), false)
            ->assertSee('Usuń witrynę Ośrodek Testowy');
    }

    public function test_bez_zgody_i_nazwy_nic_nie_jest_usuwane(): void
    {
        $this->content();
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.witryny.confirm-destroy', $this->sub))
            ->delete(route('admin.witryny.destroy', $this->sub), ['confirm_name' => 'zła nazwa'])
            ->assertSessionHasErrors(['with_content', 'confirm_name']);

        $this->assertNotNull($this->sub->fresh());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->sub->id)->count());
    }

    public function test_wymuszone_usuniecie_z_trescia_zostawia_inne_witryny(): void
    {
        $this->content();

        $this->actingAs($this->admin())
            ->delete(route('admin.witryny.destroy', $this->sub), ['with_content' => '1', 'confirm_name' => 'Ośrodek Testowy', 'backup' => '0'])
            ->assertRedirect(route('admin.witryny.index'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Usunięto witrynę „Ośrodek Testowy” i 2 powiązanych rekordów'));

        $this->assertNull(SiteSetting::find($this->sub->id));
        $this->assertSame(0, Page::withoutGlobalScopes()->withTrashed()->where('site_id', $this->sub->id)->count());
        $this->assertSame(0, News::withoutGlobalScopes()->where('site_id', $this->sub->id)->count());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('slug', 'glowna-strona')->count());
    }

    public function test_pusta_witryna_usuwana_od_razu_glowna_nigdy(): void
    {
        $this->actingAs($this->admin())->delete(route('admin.witryny.destroy', $this->sub))->assertRedirect(route('admin.witryny.index'));
        $this->assertNull(SiteSetting::find($this->sub->id));

        $this->actingAs($this->admin())->delete(route('admin.witryny.destroy', $this->main))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.witryny.confirm-destroy', $this->main))->assertForbidden();
    }

    public function test_witryny_z_podwitrynami_nie_mozna_usunac(): void
    {
        $child = SiteSetting::create([
            'site_name' => 'BIP Ośrodka', 'slug' => 'bip-osrodka', 'parent_site_id' => $this->sub->id,
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);

        $this->actingAs($this->admin())->get(route('admin.witryny.confirm-destroy', $this->sub))->assertOk()
            ->assertSee('Najpierw usuń podwitryny')->assertSee('BIP Ośrodka');
        $this->actingAs($this->admin())->delete(route('admin.witryny.destroy', $this->sub), ['with_content' => '1', 'confirm_name' => 'Ośrodek Testowy'])
            ->assertRedirect(route('admin.witryny.confirm-destroy', $this->sub));
        $this->assertNotNull($this->sub->fresh());
        $this->assertNotNull($child->fresh());
    }
}
