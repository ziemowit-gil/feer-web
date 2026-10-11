<?php

namespace Tests\Feature;

use App\Models\BipDocument;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BIP jako sub-witryna sieci: własna strona główna, własne dokumenty, przekierowania z /bip. */
class BipSiteTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $bip;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $this->main = SiteSetting::current();
        $this->bip = SiteSetting::create([
            'site_name' => 'BIP Fundacji', 'slug' => 'bip-fundacji', 'domain' => 'bip.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'bip',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function document(SiteSetting $site, string $title, string $slug): BipDocument
    {
        $document = new BipDocument([
            'title' => $title, 'slug' => $slug,
            'category' => 'subject_status', 'content' => '<p>Treść</p>', 'is_published' => true,
        ]);
        $document->site_id = $site->id;
        $document->save();

        return $document;
    }

    public function test_bip_site_home_is_the_bip_with_its_own_documents(): void
    {
        $this->document($this->bip, 'Statut w BIP', 'statut');
        $this->document($this->main, 'Dokument głównej witryny', 'inny');

        $this->get('http://bip.example.test/')
            ->assertOk()
            ->assertSee('Biuletyn Informacji Publicznej')
            ->assertSee('Statut w BIP')
            ->assertDontSee('Dokument głównej witryny');

        $this->get('http://bip.example.test/bip/statut')->assertOk()->assertSee('Statut w BIP');
        $this->get('http://bip.example.test/bip/inny')->assertNotFound();
    }

    public function test_bip_site_is_reachable_by_slug(): void
    {
        $this->document($this->bip, 'Statut w BIP', 'statut');

        $this->get('/bip-fundacji')->assertOk()->assertSee('Statut w BIP');
        $this->get('/bip-fundacji/bip/statut')->assertOk()->assertSee('Statut w BIP');
    }

    public function test_main_site_bip_path_shows_bip_site(): void
    {
        $this->document($this->bip, 'Statut w BIP', 'statut');
        $this->document($this->main, 'Dokument głównej witryny', 'inny');

        $this->get('/bip')
            ->assertOk()
            ->assertSee('Statut w BIP')
            ->assertDontSee('Dokument głównej witryny')
            ->assertSee(url('/bip/statut'), false)
            ->assertSee(url('/bip/rejestr-zmian'), false);

        $this->forgetCurrentSite();
        $this->get('/bip/statut')->assertOk()->assertSee('Statut w BIP')->assertSee('href="'.url('/bip').'" class="underline', false);
        $this->forgetCurrentSite();
        $this->get('/bip/inny')->assertNotFound();
        $this->forgetCurrentSite();
        $this->get('/bip/rejestr-zmian')->assertOk()->assertSee('BIP Fundacji');
        $this->forgetCurrentSite();
        $this->get('/bip/instrukcja')->assertOk()->assertSee('BIP Fundacji');
    }

    /** Aplikacja w teście żyje między żądaniami — witryna związana przez poprzednie żądanie nie może przeciekać. */
    private function forgetCurrentSite(): void
    {
        app()->forgetInstance(SiteSetting::CURRENT_SITE_BINDING);
    }

    public function test_main_site_links_to_bip_site(): void
    {
        $this->assertSame('https://bip.example.test', $this->main->bipHref());
        $this->assertFalse($this->main->bipIsExternal());
    }

    public function test_admin_creates_bip_site_and_moves_main_site_documents(): void
    {
        $this->bip->delete();
        $document = $this->document($this->main, 'Statut', 'statut');
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.witryny.create'))->assertOk()->assertSee('Rodzaj witryny');

        $this->actingAs($admin)->post(route('admin.witryny.store'), [
            'site_name' => 'BIP', 'slug' => 'biuletyn', 'site_kind' => 'bip', 'move_bip_content' => '1',
        ])->assertRedirect(route('admin.witryny.index'));

        $site = SiteSetting::where('slug', 'biuletyn')->firstOrFail();
        $this->assertTrue($site->isBipSite());
        $this->assertSame($site->id, $document->fresh()->site_id);

        // Druga witryna BIP tej samej witryny nadrzędnej jest odrzucana.
        $this->actingAs($admin)->post(route('admin.witryny.store'), [
            'site_name' => 'BIP 2', 'slug' => 'biuletyn-2', 'site_kind' => 'bip',
        ])->assertSessionHasErrors('site_kind');
    }

    public function test_seeder_creates_bip_site_under_bip_path_and_moves_documents(): void
    {
        $this->bip->delete();
        $document = $this->document($this->main, 'Statut', 'statut');

        $this->seed(\Database\Seeders\BipSiteSeeder::class);
        $this->seed(\Database\Seeders\BipSiteSeeder::class); // idempotentny

        $site = SiteSetting::where('site_kind', 'bip')->sole();
        $this->assertSame('bip', $site->slug);
        $this->assertSame($site->id, $document->fresh()->site_id);
        $this->assertSame(url('/bip'), SiteSetting::find($this->main->id)->bipHref());
        $this->assertSame(url('/bip/statut'), $document->fresh()->publicUrl());

        $this->forgetCurrentSite();
        $this->get('/bip')->assertOk()->assertSee('Statut');
    }

    public function test_bip_slug_is_allowed_only_for_bip_site(): void
    {
        $this->bip->delete();
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'Ośrodek', 'slug' => 'bip', 'site_kind' => 'standard'])
            ->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'BIP', 'slug' => 'bip', 'site_kind' => 'bip'])
            ->assertSessionHasNoErrors();
    }
}
