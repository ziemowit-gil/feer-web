<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\PageBlocksController;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** „Treść strony” w karcie Strony Nawigator: lista bloków, przesuwanie, ukrywanie, szybka edycja tekstu. */
class PageBlocksCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $settings = SiteSetting::current();
        if (! $settings->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony w ustawieniach testowych.');
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function pageWithBlocks(): Page
    {
        $blocks = BlockRenderer::normalize([
            ['type' => 'heading', 'attrs' => ['text' => 'Witamy', 'level' => 2]],
            ['type' => 'paragraph', 'attrs' => ['text' => 'Pierwszy akapit']],
            ['type' => 'paragraph', 'attrs' => ['text' => 'Z <a href="https://example.org">linkiem</a>']],
        ]);
        $page = Page::create(['title' => 'Bloki', 'slug' => 'bloki', 'type' => 'standard', 'is_published' => true]);
        $page->updateFromBlockEditor(['blocks' => $blocks, 'content' => BlockRenderer::render($blocks)]);

        return $page->fresh();
    }

    private function v(Page $page): string
    {
        return PageBlocksController::version($page->fresh());
    }

    public function test_karta_pokazuje_bloki_z_akcjami(): void
    {
        $page = $this->pageWithBlocks();

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();

        $this->assertStringContainsString('id="tresc-strony"', $html);
        $this->assertStringContainsString('(3 bloki)', $html);
        $this->assertStringContainsString('H2: Witamy', $html);
        $this->assertStringContainsString(route('admin.podstrony.blok.przesun', [$page, 0]), $html);
        $this->assertStringContainsString('id="blk-edit-1"', $html);       // akapit bez formatowania — szybka edycja
        $this->assertStringNotContainsString('id="blk-edit-2"', $html);    // akapit z linkiem — tylko pełny edytor
    }

    public function test_przesuwanie_bloku_zmienia_kolejnosc_i_tresc(): void
    {
        $page = $this->pageWithBlocks();

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.blok.przesun', [$page, 1]), ['v' => $this->v($page), 'kierunek' => 'gora'])
            ->assertRedirect()->assertSessionHas('status');

        $page->refresh();
        $this->assertSame('paragraph', $page->blocks[0]['type']);
        $this->assertLessThan(strpos($page->content, 'Witamy'), strpos($page->content, 'Pierwszy akapit'));
    }

    public function test_ukryty_blok_zostaje_w_blokach_ale_znika_ze_strony(): void
    {
        $page = $this->pageWithBlocks();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.podstrony.blok.widocznosc', [$page, 1]), ['v' => $this->v($page)])->assertRedirect();
        $page->refresh();
        $this->assertTrue($page->blocks[1]['hidden']);
        $this->assertStringNotContainsString('Pierwszy akapit', $page->content);
        $this->assertCount(3, $page->blocks);

        $this->actingAs($admin)->post(route('admin.podstrony.blok.widocznosc', [$page, 1]), ['v' => $this->v($page)])->assertRedirect();
        $page->refresh();
        $this->assertArrayNotHasKey('hidden', $page->blocks[1]);
        $this->assertStringContainsString('Pierwszy akapit', $page->content);
    }

    public function test_szybka_edycja_akapitu_i_naglowka(): void
    {
        $page = $this->pageWithBlocks();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.podstrony.blok.zapisz', [$page, 1]), ['v' => $this->v($page), 'tekst' => "Nowy <tekst>\nDrugi wiersz"])->assertRedirect();
        $page->refresh();
        $this->assertStringContainsString('Nowy &lt;tekst&gt;<br>Drugi wiersz', $page->content);

        $this->actingAs($admin)->put(route('admin.podstrony.blok.zapisz', [$page, 0]), ['v' => $this->v($page), 'tekst' => 'Dzień dobry', 'poziom' => 3])->assertRedirect();
        $page->refresh();
        $this->assertStringContainsString('<h3>Dzień dobry</h3>', $page->content);
        $this->assertNotNull($page->blocks);   // bloki nie zostały wyzerowane
    }

    public function test_blok_z_formatowaniem_nie_ma_szybkiej_edycji(): void
    {
        $page = $this->pageWithBlocks();

        $this->actingAs($this->admin())->put(route('admin.podstrony.blok.zapisz', [$page, 2]), ['v' => $this->v($page), 'tekst' => 'x'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertStringContainsString('linkiem', $page->fresh()->content);
    }

    public function test_nieaktualna_wersja_blokow_jest_odrzucana(): void
    {
        $page = $this->pageWithBlocks();

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.blok.przesun', [$page, 1]), ['v' => 'stara', 'kierunek' => 'gora'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame('heading', $page->fresh()->blocks[0]['type']);
    }

    public function test_udostepnienie_bloku_robi_blok_wspolny_bez_zmiany_wygladu(): void
    {
        $page = $this->pageWithBlocks();

        $this->actingAs($this->admin())->post(route('admin.podstrony.blok.udostepnij', [$page, 1]), ['v' => $this->v($page)])
            ->assertRedirect()->assertSessionHas('status');

        $shared = \App\Models\ContentBlock::where('type', \App\Models\ContentBlock::SHARED)->firstOrFail();
        $page->refresh();
        $this->assertSame('[blok:'.$shared->id.']', $page->blocks[1]['attrs']['code']);
        $this->assertStringContainsString('Pierwszy akapit', \App\Support\ShortcodeParser::render($page->content));
        $this->assertSame(1, $shared->usedOnPages()->count());
    }

    public function test_blok_wspolny_wstawiony_na_drugiej_stronie_i_poprawiony_raz_zmienia_obie(): void
    {
        $page = $this->pageWithBlocks();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.podstrony.blok.udostepnij', [$page, 1]), ['v' => $this->v($page)]);
        $shared = \App\Models\ContentBlock::where('type', \App\Models\ContentBlock::SHARED)->firstOrFail();

        $other = Page::create(['title' => 'Druga', 'slug' => 'druga', 'type' => 'standard', 'is_published' => true]);
        $other->updateFromBlockEditor(['blocks' => [], 'content' => '']);
        $this->actingAs($admin)->post(route('admin.podstrony.blok-wspolny.wstaw', $other), ['v' => $this->v($other), 'blok' => $shared->id])->assertRedirect();
        $this->assertSame(2, $shared->usedOnPages()->count());

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $other->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Blok wspólny', $html);
        $this->assertStringContainsString('na stronach: 2', $html);

        $this->actingAs($admin)->put(route('admin.podstrony.blok-wspolny.zapisz', [$other, $shared]), ['tekst' => 'Wspólny nowy tekst'])->assertRedirect()->assertSessionHas('status');
        $this->assertStringContainsString('Wspólny nowy tekst', \App\Support\ShortcodeParser::render($page->fresh()->content));
        $this->assertStringContainsString('Wspólny nowy tekst', \App\Support\ShortcodeParser::render($other->fresh()->content));
    }

    public function test_odlaczenie_daje_kopie_niezalezna_od_bloku_wspolnego(): void
    {
        $page = $this->pageWithBlocks();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.podstrony.blok.udostepnij', [$page, 1]), ['v' => $this->v($page)]);
        $shared = \App\Models\ContentBlock::where('type', \App\Models\ContentBlock::SHARED)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.podstrony.blok.odlacz', [$page, 1]), ['v' => $this->v($page)])->assertRedirect()->assertSessionHas('status');
        $page->refresh();
        $this->assertSame('paragraph', $page->blocks[1]['type']);

        $this->actingAs($admin)->put(route('admin.podstrony.blok-wspolny.zapisz', [$page, $shared]), ['tekst' => 'Zmieniony']);
        $this->assertStringNotContainsString('Zmieniony', $page->fresh()->content);
        $this->assertStringContainsString('Pierwszy akapit', $page->fresh()->content);
    }

    public function test_kreator_klasycznego_edytora_nie_pokazuje_blokow_wspolnych(): void
    {
        $page = $this->pageWithBlocks();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.podstrony.blok.udostepnij', [$page, 1]), ['v' => $this->v($page)]);

        $this->actingAs($admin)->getJson(route('admin.bloki-tresci.index'))->assertOk()->assertJsonCount(0);
    }
}
