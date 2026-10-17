<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Blok „Baner sekcji” w edytorze blokowym: grafika z ukosem po lewej, tytuł sekcji (nagłówek) po prawej. */
class BlockBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_baner_z_grafika_dekoracyjna_i_tytulem_jako_naglowek(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'banner', 'attrs' => ['title' => '  Nasze   media społecznościowe ', 'url' => '/storage/1/social.jpg']],
        ]);

        $this->assertSame(
            '<div class="content-banner content-banner--large content-banner--gray"><div class="content-banner-media"><img src="/storage/1/social.jpg" alt="" loading="lazy"></div>'
            .'<svg class="content-banner-lines" viewBox="0 0 45 100" preserveAspectRatio="none" aria-hidden="true" focusable="false"><line x1="36" y1="0" x2="6" y2="100" /><line x1="44" y1="0" x2="14" y2="100" /></svg>'
            .'<h2 class="content-banner-title">Nasze media społecznościowe</h2></div>',
            $html
        );
    }

    public function test_baner_z_odnosnikiem_to_jeden_link_z_nazwa_z_tytulu(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'banner', 'attrs' => ['title' => 'Aktualności z placówek', 'level' => 3, 'size' => 'small', 'tone' => 'brand', 'href' => '/aktualnosci', 'url' => '/m.jpg', 'decorative' => false, 'alt' => 'Mapa świata z zaznaczonymi placówkami']],
        ]);

        $this->assertStringContainsString('content-banner--small content-banner--brand', $html);
        $this->assertStringContainsString('alt="Mapa świata z zaznaczonymi placówkami"', $html);
        $this->assertStringContainsString('<h3 class="content-banner-title"><a href="/aktualnosci" class="content-banner-link">Aktualności z placówek</a></h3>', $html);
        $this->assertSame(1, substr_count($html, '<a '));
    }

    public function test_baner_bez_grafiki_bez_tytulu_i_z_niebezpiecznym_adresem(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'banner', 'attrs' => ['title' => 'Sam <b>tytuł</b>', 'href' => 'javascript:alert(1)', 'url' => 'data:image/png;base64,xx', 'size' => 'ogromny', 'level' => 9]],
            ['type' => 'banner', 'attrs' => ['title' => '   ', 'url' => '/x.jpg']],
        ]);

        $this->assertSame('<div class="content-banner content-banner--large content-banner--gray content-banner--no-media"><h4 class="content-banner-title">Sam &lt;b&gt;tytuł&lt;/b&gt;</h4></div>', $html);
    }

    public function test_admin_zapisuje_baner_z_edytora_i_widac_go_na_stronie(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>Stara</p>', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->putJson(route('admin.podstrony.bloki.update', $page), [
                'title' => 'Strona',
                'blocks' => [['id' => 'b1', 'type' => 'banner', 'attrs' => ['title' => 'Nasze media społecznościowe', 'url' => '/s.jpg', 'href' => '/media']]],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $page->refresh();
        $this->assertSame('banner', $page->blocks[0]['type']);
        $this->assertTrue($page->blocks[0]['attrs']['decorative']);

        $html = $this->get('/strona')->assertOk()->getContent();
        // Na stronie nagłówek dostaje kotwicę do spisu treści.
        $this->assertMatchesRegularExpression('~<h2 class="content-banner-title"( id="[a-z0-9-]+")?><a href="/media" class="content-banner-link">Nasze media społecznościowe</a></h2>~u', $html);
    }

    public function test_baner_graficzny_to_jeden_link_z_tekstem_z_grafiki(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'imagebanner', 'attrs' => ['url' => '/storage/1/paszport.webp', 'alt' => ' Zarezerwuj  termin w punkcie paszportowym ', 'href' => 'https://rezerwacja.example.pl', 'newTab' => true]],
            ['type' => 'imagebanner', 'attrs' => ['url' => '/storage/1/poradnik.webp', 'alt' => 'Poradnik bezpieczeństwa. Pobierz poradnik', 'href' => '/poradnik']],
        ]);

        $this->assertStringContainsString(
            '<div class="content-imagebanner"><a href="https://rezerwacja.example.pl" class="content-imagebanner-link" target="_blank" rel="noopener">'
            .'<img src="/storage/1/paszport.webp" alt="Zarezerwuj termin w punkcie paszportowym" loading="lazy"><span class="sr-only"> (otwiera się w nowej karcie)</span></a></div>',
            $html
        );
        $this->assertStringContainsString('<div class="content-imagebanner"><a href="/poradnik" class="content-imagebanner-link"><img src="/storage/1/poradnik.webp" alt="Poradnik bezpieczeństwa. Pobierz poradnik" loading="lazy"></a></div>', $html);
    }

    public function test_baner_graficzny_bez_grafiki_znika_a_bez_adresu_jest_obrazem(): void
    {
        $this->assertSame('', BlockRenderer::render([['type' => 'imagebanner', 'attrs' => ['alt' => 'Coś', 'href' => '/x']]]));
        $this->assertSame(
            '<div class="content-imagebanner"><img src="/b.jpg" alt="Kalendarz badań" loading="lazy"></div>',
            BlockRenderer::render([['type' => 'imagebanner', 'attrs' => ['url' => '/b.jpg', 'alt' => 'Kalendarz badań', 'href' => 'javascript:alert(1)']]])
        );
    }

    public function test_baner_graficzny_ma_wymiary_grafiki_i_zalecenia_rozmiaru(): void
    {
        $html = BlockRenderer::render([['type' => 'imagebanner', 'attrs' => ['url' => '/b.webp', 'alt' => 'Kalendarz badań', 'width' => 2400, 'height' => 240]]]);
        $this->assertSame('<div class="content-imagebanner"><img src="/b.webp" alt="Kalendarz badań" width="2400" height="240" loading="lazy"></div>', $html);

        $this->assertStringNotContainsString('width=', BlockRenderer::render([['type' => 'imagebanner', 'attrs' => ['url' => '/b.webp', 'alt' => 'X', 'width' => -5, 'height' => 'abc']]]));

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('min-height: 7rem; margin: 1.5rem 0;', $css); // równa wysokość banerów sekcji
        $this->assertStringContainsString('.content-imagebanner { max-width: 1200px; }', $css);
        $this->assertStringContainsString('1200 × 120 px (proporcje 10:1)', file_get_contents(resource_path('js/block-editor.js')));
    }

    public function test_edytor_zna_blok_banera_i_style_sa_w_css(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));
        $this->assertStringContainsString("banner: { title: 'Baner sekcji'", $js);
        $this->assertStringContainsString("cls.contains('content-banner')", $js); // odczyt z HTML (Przekształć w bloki)
        $this->assertStringContainsString("imagebanner: { title: 'Baner graficzny'", $js);
        $this->assertStringContainsString("cls.contains('content-imagebanner')", $js);
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('.content-banner .content-banner-link:focus-visible::after', $css);
        $this->assertStringContainsString('.content-imagebanner-link:focus-visible', $css);
    }
}
