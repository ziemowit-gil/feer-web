<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuAndBipAdminTest extends TestCase
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

    public function test_strony_nie_maja_zakladek_menu_a_menu_ma_wlasne(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()
            ->assertSee('aria-label="Sekcje stron"', false)
            ->assertDontSee('>Menu główne</a>', false)
            ->assertDontSee('Menu BIP</a>', false);

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'footer']))->assertOk()
            ->assertSee('aria-label="Sekcje menu"', false)
            ->assertSee('Menu główne')
            ->assertSee('Stopka')
            ->assertDontSee('Lista stron');

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'bip']))->assertOk()
            ->assertSee('aria-label="Sekcje BIP"', false)
            ->assertSee('<title>BIP — menu boczne', false);
    }

    public function test_bip_jest_osobnym_blokiem_w_grupie_dokumenty(): void
    {
        $this->actingAs($admin = $this->admin());
        request()->query->set('location', 'bip');
        $docs = collect(AdminMenu::for($admin))->firstWhere('key', 'docs');

        $bip = collect($docs['blocks'])->firstWhere('heading', 'Biuletyn (BIP)');
        $this->assertNotNull($bip);
        $this->assertContains('bip-menu', array_column($bip['items'], 'key'));
        $this->assertStringContainsString('location=bip', $bip['items'][0]['url']);

        $treści = collect(AdminMenu::for($admin))->firstWhere('key', 'content');
        $this->assertNotContains('bip', collect($treści['blocks'])->flatMap(fn ($b) => array_column($b['items'], 'key'))->all());
    }

    public function test_pozycja_prowadzaca_do_strony_ma_przycisk_edytora_blokowego(): void
    {
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => false]);
        $item = NavItem::create(['label' => 'Fundacja', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Zewnętrzny', 'url' => 'https://example.org', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 2]);

        $this->actingAs($this->admin())->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $item->id]))->assertOk()
            ->assertSee(route('admin.podstrony.bloki.edit', $page), false)
            ->assertSee('Edytuj w edytorze blokowym')
            ->assertSee('(nieopublikowana)');

        $this->assertNull(NavItem::where('label', 'Zewnętrzny')->first()->editablePage());
    }
}
