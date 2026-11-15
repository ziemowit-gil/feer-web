<?php

namespace Tests\Feature;

use App\Services\Content\ContentA11yChecker;
use App\Services\Content\ContentA11yFixer;
use App\Support\BlockRenderer;
use Tests\TestCase;

class ContentA11yFixerTest extends TestCase
{
    private function rules(string $html): array
    {
        return array_column(app(ContentA11yChecker::class)->check($html), 'rule');
    }

    public function test_html_po_poprawkach_nie_ma_uwag_ktore_da_sie_poprawic_automatycznie(): void
    {
        $html = '<h2></h2><p>Wstęp – zażółć gęślą jaźń.</p><p><strong>Pomagające w orientacji</strong></p><p>Laski, GPS.</p>'
            .'<h4>Dalej</h4><p>- jeden</p><p>- <em>dwa</em></p>'
            .'<table><tr><td>A</td><td>B</td></tr><tr><td>1</td><td>2</td></tr></table>'
            .'<iframe src="https://www.youtube.com/embed/x"></iframe><p>Koniec</p>';

        ['html' => $out, 'fixes' => $fixes] = app(ContentA11yFixer::class)->fixHtml($html);

        $this->assertSame([], $this->rules($out), $out);
        $this->assertSame(1, $fixes['heading_empty']);
        $this->assertSame(1, $fixes['fake_heading']);
        $this->assertSame(1, $fixes['fake_list']);
        $this->assertSame(1, $fixes['table_header']);
        $this->assertSame(1, $fixes['iframe_title']);
        $this->assertStringContainsString('<h2>Pomagające w orientacji</h2>', $out);
        $this->assertStringContainsString('<h3>Dalej</h3>', $out);
        $this->assertStringContainsString('<ul><li>jeden</li><li><em>dwa</em></li></ul>', $out);
        $this->assertStringContainsString('<th scope="col">A</th>', $out);
        $this->assertStringContainsString('title="Film YouTube"', $out);
        $this->assertStringContainsString('zażółć gęślą jaźń', $out);
    }

    public function test_pogrubienia_pod_nagloweknem_staja_sie_podsekcjami_a_numerowane_akapity_lista_numerowana(): void
    {
        $html = '<h2>Sprzęt</h2><p><b>Optyczne</b></p><p>Lupy</p><p><b>Dźwiękowe</b></p><p>Odtwarzacze</p><p>1. raz</p><p>2) dwa</p>';
        ['html' => $out] = app(ContentA11yFixer::class)->fixHtml($html);

        $this->assertStringContainsString('<h3>Optyczne</h3>', $out);
        $this->assertStringContainsString('<h3>Dźwiękowe</h3>', $out);
        $this->assertStringContainsString('<ol><li>raz</li><li>dwa</li></ol>', $out);
        $this->assertSame([], $this->rules($out));
    }

    public function test_poprawna_tresc_zostaje_bez_zmian_a_linki_i_obrazy_nie_sa_ruszane(): void
    {
        $html = '<h2>Tytuł</h2><p>Tekst <a href="/x">tutaj</a>.</p><img src="/a.jpg">';
        $result = app(ContentA11yFixer::class)->fixHtml($html);

        $this->assertSame($html, $result['html']);
        $this->assertSame([], $result['fixes']);
    }

    public function test_tylko_wybrane_poprawki(): void
    {
        $html = '<h4>Start</h4><p><strong>Pogrubione</strong></p><p>tekst</p>';
        ['html' => $out, 'fixes' => $fixes] = app(ContentA11yFixer::class)->only(['heading_first'])->fixHtml($html);

        $this->assertSame(['headings' => 1], $fixes);
        $this->assertStringContainsString('<h2>Start</h2>', $out);
        $this->assertStringContainsString('<strong>Pogrubione</strong>', $out);
    }

    public function test_bloki_poprawiane_w_blokach(): void
    {
        $blocks = [
            ['type' => 'heading', 'attrs' => ['text' => '', 'level' => 2]],
            ['type' => 'paragraph', 'attrs' => ['text' => '<strong>Tytuł sekcji</strong>']],
            ['type' => 'paragraph', 'attrs' => ['text' => 'tekst']],
            ['type' => 'heading', 'attrs' => ['text' => 'Podsekcja', 'level' => 4]],
            ['type' => 'paragraph', 'attrs' => ['text' => '- a']],
            ['type' => 'paragraph', 'attrs' => ['text' => '- <b>b</b>']],
            ['type' => 'table', 'attrs' => ['headerRow' => false, 'rows' => [['A', 'B'], ['1', '2']]]],
            ['type' => 'embed', 'attrs' => ['url' => 'https://vimeo.com/1', 'title' => '']],
        ];

        ['blocks' => $out, 'fixes' => $fixes] = app(ContentA11yFixer::class)->fixBlocks($blocks);

        $this->assertSame(['heading', 'paragraph', 'heading', 'list', 'table', 'embed'], array_column($out, 'type'));
        $this->assertSame(2, $out[0]['attrs']['level']);
        $this->assertSame(3, $out[2]['attrs']['level']);
        $this->assertSame(['a', '<b>b</b>'], $out[3]['attrs']['items']);
        $this->assertTrue($out[4]['attrs']['headerRow']);
        $this->assertSame('Film Vimeo', $out[5]['attrs']['title']);
        $this->assertSame(['heading_empty', 'fake_heading', 'table_header', 'iframe_title', 'fake_list', 'headings'], array_keys($fixes));
        $this->assertSame([], $this->rules(BlockRenderer::render($out)));
    }
}
