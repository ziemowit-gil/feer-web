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

    public function test_main_site_bip_redirects_to_bip_site(): void
    {
        $this->get('/bip')->assertRedirect('https://bip.example.test');
        $this->get('/bip/statut')->assertRedirect('https://bip.example.test/bip/statut');
        $this->get('/bip/rejestr-zmian')->assertRedirect('https://bip.example.test/bip/rejestr-zmian');
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
}
