<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Menu nawigacji: eksport struktury do XML i dopisywanie pozycji z pliku XML. */
class NavItemsXmlTest extends TestCase
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

    private function xmlFile(string $xml): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('menu.xml', $xml);
    }

    public function test_menu_dodaj_ma_opcje_ze_stron_i_z_pliku_xml_oraz_eksport(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pozycje-menu.index'))->assertOk()
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSee('Nowa pozycja')->assertSee('Ze stron')->assertSee('Z pliku XML')
            ->assertSee('Eksport XML')->assertSee('Dodaj pozycje z pliku XML');
    }

    public function test_eksport_i_import_odtwarza_strukture(): void
    {
        $admin = $this->admin();
        $parent = NavItem::create(['label' => 'Fundacja', 'url' => '/fundacja', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1,
            'is_mega' => true, 'mega_side_links' => [['label' => 'Raporty', 'url' => '/raporty', 'style' => 'button', 'new_tab' => true]]]);
        NavItem::create(['label' => 'O nas & zespół', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'parent_id' => $parent->id, 'is_active' => false, 'order' => 1]);

        $res = $this->actingAs($admin)->get(route('admin.pozycje-menu.eksport-xml', ['location' => 'main']))->assertOk();
        $this->assertStringContainsString('attachment; filename="menu-main-', $res->headers->get('Content-Disposition'));
        $xml = $res->getContent();
        $this->assertStringContainsString('<menu location="main"', $xml);
        $this->assertStringContainsString('label="O nas &amp; zespół"', $xml);

        NavItem::query()->delete();
        $this->actingAs($admin)->post(route('admin.pozycje-menu.import-xml'), ['location' => 'main', 'xml' => $this->xmlFile($xml)])
            ->assertRedirect()->assertSessionHas('status', 'Zaimportowano pozycji menu z pliku XML: 2.');

        $top = NavItem::whereNull('parent_id')->sole();
        $this->assertSame('Fundacja', $top->label);
        $this->assertTrue($top->is_mega);
        $this->assertSame('/raporty', $top->mega_side_links[0]['url']);
        $this->assertTrue($top->mega_side_links[0]['new_tab']);
        $child = NavItem::where('parent_id', $top->id)->sole();
        $this->assertSame('O nas & zespół', $child->label);
        $this->assertFalse($child->is_active);
    }

    public function test_import_do_stopki_splaszcza_i_odrzuca_niebezpieczne_dane(): void
    {
        $xml = '<?xml version="1.0"?><menu location="main">'
            .'<item label="Zły" url="javascript:alert(1)" type="nieznany" button_color="red"><item label="Dziecko" url="/d"/></item>'
            .'<item url="/bez-etykiety"/></menu>';

        $this->actingAs($this->admin())->post(route('admin.pozycje-menu.import-xml'), ['location' => 'footer', 'xml' => $this->xmlFile($xml)])
            ->assertSessionHas('status', 'Zaimportowano pozycji menu z pliku XML: 2.');

        $bad = NavItem::where('label', 'Zły')->sole();
        $this->assertSame('#', $bad->url);
        $this->assertSame('link', $bad->type);
        $this->assertNull($bad->button_color);
        $this->assertNull(NavItem::where('label', 'Dziecko')->sole()->parent_id);
        $this->assertSame(2, NavItem::where('location', 'footer')->count());
    }

    public function test_import_odrzuca_doctype_i_obcy_plik(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.pozycje-menu.import-xml'), ['location' => 'main',
            'xml' => $this->xmlFile('<?xml version="1.0"?><!DOCTYPE m [<!ENTITY x SYSTEM "file:///etc/passwd">]><menu><item label="&x;"/></menu>')])
            ->assertSessionHasErrors('xml');
        $this->actingAs($admin)->post(route('admin.pozycje-menu.import-xml'), ['location' => 'main', 'xml' => $this->xmlFile('<rss><item/></rss>')])
            ->assertSessionHasErrors('xml');
        $this->assertSame(0, NavItem::count());
    }
}
