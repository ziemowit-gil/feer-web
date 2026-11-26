<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DocxToHtml;
use App\Support\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/** Parser stylów Worda (DocxToHtml): dokument jak z polskiego Worda → semantyczny HTML dla bloków. */
class DocxToHtmlTest extends TestCase
{
    use RefreshDatabase;

    private const NS = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"';

    private function p(string $runs, string $style = '', string $num = ''): string
    {
        $ppr = ($style ? '<w:pStyle w:val="'.$style.'"/>' : '').$num;

        return '<w:p>'.($ppr ? '<w:pPr>'.$ppr.'</w:pPr>' : '').$runs.'</w:p>';
    }

    private function r(string $text, string $rpr = ''): string
    {
        return '<w:r>'.($rpr ? '<w:rPr>'.$rpr.'</w:rPr>' : '').'<w:t xml:space="preserve">'.htmlspecialchars($text).'</w:t></w:r>';
    }

    private function docx(): string
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $num = fn ($id, $lvl = 0) => '<w:numPr><w:ilvl w:val="'.$lvl.'"/><w:numId w:val="'.$id.'"/></w:numPr>';
        $img = '<w:r><w:drawing><wp:inline><wp:docPr id="1" name="Obraz 1" descr="Wolontariusze przy stoisku"/><a:graphic><a:graphicData><pic:pic><pic:blipFill><a:blip r:embed="rIdImg"/></pic:blipFill></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
        $cell = fn ($t, $b = false) => '<w:tc>'.$this->p($this->r($t, $b ? '<w:b/>' : '')).'</w:tc>';

        $body = implode('', [
            $this->p($this->r('Sprawozdanie'), 'Tytu'),
            $this->p($this->r('Wstęp'), 'Nagwek1'),
            $this->p($this->r('Zwykły ').$this->r('ważny', '<w:b/>').$this->r(' i ').$this->r('pochyły', '<w:i/>').$this->r(' tekst, m').$this->r('2', '<w:vertAlign w:val="superscript"/>').$this->r('.')),
            $this->p('<w:hyperlink r:id="rIdLink">'.$this->r('Strona fundacji').'</w:hyperlink>'),
            $this->p(''),
            $this->p($this->r('Pierwszy punkt'), 'Akapitzlist', $num(1)),
            $this->p($this->r('Drugi punkt'), 'Akapitzlist', $num(1, 1)),
            $this->p($this->r('Krok pierwszy'), 'Akapitzlist', $num(2)),
            $this->p($this->r('Krok drugi'), 'Akapitzlist', $num(2)),
            $this->p($this->r('Pogrubiony śródtytuł', '<w:b/>')),
            $this->p($this->r('Uwaga: to zwykłe zdanie.', '<w:b/>')),
            $this->p($this->r('Mądre słowa'), 'Cytat'),
            '<w:tbl><w:tr>'.$cell('Imię', true).$cell('Rola', true).'</w:tr><w:tr>'.$cell('Ala').$cell('Koordynatorka').'</w:tr></w:tbl>',
            '<w:tbl><w:tr>'.$cell('Zapisy do 30 listopada').'</w:tr></w:tbl>',
            $this->p($img),
            $this->p($this->r('Rys. 1. Stoisko'), 'Legenda'),
            $this->p($this->r('Szczegóły'), 'Nagwek3'),
            $this->p($this->r('Ukryty', '<w:vanish/>').$this->r('Widoczny')),
        ]);

        $styles = '<w:styles '.self::NS.'>'
            .'<w:style w:type="paragraph" w:styleId="Normalny"><w:name w:val="Normal"/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Tytu"><w:name w:val="Title"/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Nagwek1"><w:name w:val="heading 1"/><w:pPr><w:outlineLvl w:val="0"/></w:pPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Nagwek3"><w:name w:val="heading 3"/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Akapitzlist"><w:name w:val="List Paragraph"/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Cytat"><w:name w:val="Quote"/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Legenda"><w:name w:val="caption"/></w:style>'
            .'</w:styles>';
        $numbering = '<w:numbering '.self::NS.'>'
            .'<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:numFmt w:val="bullet"/></w:lvl><w:lvl w:ilvl="1"><w:numFmt w:val="bullet"/></w:lvl></w:abstractNum>'
            .'<w:abstractNum w:abstractNumId="1"><w:lvl w:ilvl="0"><w:numFmt w:val="decimal"/></w:lvl></w:abstractNum>'
            .'<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num><w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num>'
            .'</w:numbering>';
        $rels = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rIdLink" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://feer.org.pl/" TargetMode="External"/>'
            .'<Relationship Id="rIdImg" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>'
            .'</Relationships>';

        $path = tempnam(sys_get_temp_dir(), 'docx_pl_').'.docx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document '.self::NS.'><w:body>'.$body.'</w:body></w:document>');
        $zip->addFromString('word/styles.xml', $styles);
        $zip->addFromString('word/numbering.xml', $numbering);
        $zip->addFromString('word/_rels/document.xml.rels', $rels);
        $zip->addFromString('word/media/image1.png', $png);
        $zip->close();

        return $path;
    }

    public function test_style_polskiego_worda_daja_semantyczny_html(): void
    {
        $stored = [];
        $parser = new DocxToHtml(function ($bytes, $name) use (&$stored) {
            $stored[] = $name;

            return '/media/'.$name;
        });
        $html = $parser->convert($path = $this->docx());
        @unlink($path);

        $this->assertStringContainsString('<h1>Sprawozdanie</h1>', $html);
        $this->assertStringContainsString('<h1>Wstęp</h1>', $html);
        $this->assertStringContainsString('<p>Zwykły <strong>ważny</strong> i <em>pochyły</em> tekst, m<sup>2</sup>.</p>', $html);
        $this->assertStringContainsString('<p><a href="https://feer.org.pl/">Strona fundacji</a></p>', $html);
        $this->assertStringContainsString('<ul><li>Pierwszy punkt</li><li>Drugi punkt</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>Krok pierwszy</li><li>Krok drugi</li></ol>', $html);
        // Autonagłówek (są prawdziwe nagłówki → H3); zdanie z dwukropkiem i kropką zostaje akapitem.
        $this->assertStringContainsString('<h3>Pogrubiony śródtytuł</h3>', $html);
        $this->assertStringContainsString('<p><strong>Uwaga: to zwykłe zdanie.</strong></p>', $html);
        $this->assertStringContainsString('<blockquote><p>Mądre słowa</p></blockquote>', $html);
        $this->assertStringContainsString('<thead><tr><th scope="col">Imię</th><th scope="col">Rola</th></tr></thead><tbody><tr><td>Ala</td><td>Koordynatorka</td></tr></tbody>', $html);
        $this->assertStringContainsString('<div class="content-box"><p>Zapisy do 30 listopada</p></div>', $html);
        $this->assertStringContainsString('<figure><img src="/media/image1.png" alt="Wolontariusze przy stoisku"><figcaption>Rys. 1. Stoisko</figcaption></figure>', $html);
        $this->assertStringContainsString('<h3>Szczegóły</h3>', $html);
        $this->assertStringContainsString('<p>Widoczny</p>', $html);
        $this->assertStringNotContainsString('Ukryty', $html);
        $this->assertSame(['image1.png'], $stored);
        $this->assertSame(1, $parser->stats['auto_headings']);
        $this->assertSame(2, $parser->stats['lists']);
    }

    public function test_endpoint_zwraca_poprawione_naglowki_i_obraz_w_bibliotece(): void
    {
        config(['app.url' => 'http://localhost', 'editions.forced' => 'pro']);
        Edition::flush();
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $path = $this->docx();

        $json = $this->actingAs($admin)->post(route('admin.editor.docx.import'), [
            'docx' => new UploadedFile($path, 'sprawozdanie.docx', null, null, true),
        ])->assertOk()->json();
        @unlink($path);

        $this->assertStringNotContainsString('<h1', $json['html']);
        $this->assertStringContainsString('<h2>Sprawozdanie</h2>', $json['html']);
        // „Tytuł” i „Nagłówek 1” z Worda są równorzędne → oba H2.
        $this->assertStringContainsString('<h2>Wstęp</h2>', $json['html']);
        $this->assertSame(1, $json['stats']['images']);
        $this->assertMatchesRegularExpression('#<img src="[^"]+\.png" alt="Wolontariusze przy stoisku">#', $json['html']);
        Edition::flush();
    }

    public function test_przycisk_importu_ma_widoczny_napis(): void
    {
        $this->assertStringContainsString("text: config.urls.docx ? 'Import z Worda'", file_get_contents(resource_path('js/block-editor.js')));
    }
}
