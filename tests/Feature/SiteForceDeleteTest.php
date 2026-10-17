<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Witryny sieci: „Usuń” przenosi do kosza na 30 dni (treść zostaje, witryna znika publicznie),
 * z kosza — przywrócenie albo trwałe usunięcie z treścią; po 30 dniach witryny:oproznij-kosz.
 */
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
            'site_name' => 'Ośrodek Testowy', 'slug' => 'osrodek-testowy', 'domain' => 'osrodek.example.test', 'parent_site_id' => $this->main->id,
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

    private function trash(): void
    {
        $this->actingAs($this->admin())
            ->delete(route('admin.witryny.destroy', $this->sub), ['force' => '1', 'confirm_name' => 'Ośrodek Testowy'])
            ->assertRedirect(route('admin.witryny.index'));
    }

    public function test_strona_potwierdzenia_mowi_o_koszu_i_liscie_witryn(): void
    {
        $this->content();

        $this->actingAs($this->admin())->get(route('admin.witryny.confirm-destroy', $this->sub))->assertOk()
            ->assertSee('Usuń witrynę „Ośrodek Testowy”', false)
            ->assertSee('Witryna trafi do kosza')
            ->assertSee('<label for="confirm-name"', false)
            ->assertSee('Wymuś usunięcie — razem z całą treścią')
            ->assertDontSee('Najpierw zrób kopię zapasową bazy'); // kopia dopiero przy trwałym usunięciu

        $this->actingAs($this->admin())->get(route('admin.witryny.index'))->assertOk()
            ->assertSee(route('admin.witryny.confirm-destroy', $this->sub), false)
            ->assertDontSee('id="h-site-trash"', false);
    }

    public function test_bez_zgody_i_nazwy_nic_sie_nie_dzieje(): void
    {
        $this->content();

        $this->actingAs($this->admin())->from(route('admin.witryny.confirm-destroy', $this->sub))
            ->delete(route('admin.witryny.destroy', $this->sub), ['confirm_name' => 'zła nazwa'])
            ->assertSessionHasErrors(['force', 'confirm_name']);

        $this->assertNull($this->sub->fresh()->trashed_at);
    }

    public function test_usuniecie_przenosi_do_kosza_tresc_zostaje_witryna_znika_publicznie(): void
    {
        $this->content();
        $this->get('http://osrodek.example.test/')->assertOk();

        $this->trash();

        $this->assertNotNull($this->sub->fresh()->trashed_at);
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->sub->id)->count());
        $this->assertSame(1, News::withoutGlobalScopes()->where('site_id', $this->sub->id)->count());

        // Domena witryny w koszu nie prowadzi już do niej (obsługuje ją witryna główna) — treść ośrodka niewidoczna.
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        app()->forgetInstance(SiteSetting::CURRENT_SITE_BINDING);
        $this->get('http://osrodek.example.test/strona-osrodka')->assertNotFound();
        $this->get('/osrodek-testowy')->assertNotFound();

        $this->actingAs($this->admin())->get(route('admin.witryny.index'))->assertOk()
            ->assertSee('id="h-site-trash"', false)
            ->assertSee('zostanie usunięta trwale '.now()->addDays(30)->format('d.m.Y'))
            ->assertSee(route('admin.witryny.restore', $this->sub), false);
    }

    public function test_przywrocenie_z_kosza_z_podwitrynami(): void
    {
        $child = SiteSetting::create([
            'site_name' => 'BIP Ośrodka', 'slug' => 'bip-osrodka', 'parent_site_id' => $this->sub->id,
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
        $this->trash();
        $this->assertNotNull($child->fresh()->trashed_at, 'Podwitryna trafia do kosza razem z witryną.');

        $this->actingAs($this->admin())->post(route('admin.witryny.restore', $this->sub))
            ->assertRedirect(route('admin.witryny.index'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Przywrócono witrynę „Ośrodek Testowy” i podwitryny (1)'));

        $this->assertNull($this->sub->fresh()->trashed_at);
        $this->assertNull($child->fresh()->trashed_at);
        $this->actingAs($this->admin())->post(route('admin.witryny.restore', $this->sub))->assertNotFound();
    }

    public function test_trwale_usuniecie_tylko_z_kosza_z_nazwa(): void
    {
        $this->content();

        // Poza koszem trwałe usunięcie jest niedostępne.
        $this->actingAs($this->admin())->delete(route('admin.witryny.force-destroy', $this->sub), ['force' => '1', 'confirm_name' => 'Ośrodek Testowy'])->assertNotFound();

        $this->trash();
        $this->actingAs($this->admin())->get(route('admin.witryny.confirm-destroy', $this->sub))->assertOk()
            ->assertSee('Usuń trwale witrynę')->assertSee('Najpierw zrób kopię zapasową bazy');

        $this->actingAs($this->admin())->from(route('admin.witryny.confirm-destroy', $this->sub))
            ->delete(route('admin.witryny.force-destroy', $this->sub), ['force' => '1', 'confirm_name' => 'Ośrodek'])
            ->assertSessionHasErrors('confirm_name');
        $this->assertNotNull($this->sub->fresh());

        $this->actingAs($this->admin())
            ->delete(route('admin.witryny.force-destroy', $this->sub), ['force' => '1', 'confirm_name' => 'Ośrodek Testowy', 'backup' => '0'])
            ->assertRedirect(route('admin.witryny.index'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Trwale usunięto witrynę „Ośrodek Testowy” i 2 powiązanych rekordów.'));

        $this->assertNull(SiteSetting::find($this->sub->id));
        $this->assertSame(0, Page::withoutGlobalScopes()->withTrashed()->where('site_id', $this->sub->id)->count());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('slug', 'glowna-strona')->count());
    }

    public function test_po_30_dniach_polecenie_usuwa_trwale_i_jest_w_harmonogramie(): void
    {
        $this->content();
        $this->trash();

        $this->travel(29)->days();
        $this->artisan('witryny:oproznij-kosz')->assertSuccessful();
        $this->assertNotNull(SiteSetting::find($this->sub->id));

        $this->travel(2)->days();
        $this->artisan('witryny:oproznij-kosz')->expectsOutputToContain('Ośrodek Testowy')->assertSuccessful();
        $this->assertNull(SiteSetting::find($this->sub->id));
        $this->assertSame(0, Page::withoutGlobalScopes()->withTrashed()->where('site_id', $this->sub->id)->count());
        $this->travelBack();

        $events = collect(app(Schedule::class)->events())->map->command->filter()->implode(' ');
        $this->assertStringContainsString('witryny:oproznij-kosz', $events);
    }

    public function test_glownej_witryny_nie_mozna_usunac(): void
    {
        $this->actingAs($this->admin())->delete(route('admin.witryny.destroy', $this->main))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.witryny.confirm-destroy', $this->main))->assertForbidden();
        $this->actingAs($this->admin())->delete(route('admin.witryny.force-destroy', $this->main))->assertForbidden();
    }

    public function test_pusta_witryna_trafia_do_kosza_bez_dodatkowego_potwierdzenia(): void
    {
        $this->actingAs($this->admin())->delete(route('admin.witryny.destroy', $this->sub))->assertRedirect(route('admin.witryny.index'));
        $this->assertNotNull($this->sub->fresh()->trashed_at);
    }
}
