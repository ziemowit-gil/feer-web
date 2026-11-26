<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Edition;
use App\Support\HeadingStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

/** Automatyczna poprawka struktury nagłówków (HeadingStructure) i import z Worda, który ją stosuje. */
class HeadingStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_naglowki_z_worda_zaczynaja_sie_od_h2_bez_splaszczania(): void
    {
        $r = HeadingStructure::fix('<h1>A</h1><p>x</p><h2 id="b">B</h2><h3>C</h3><h2>D</h2>');

        $this->assertSame('<h2>A</h2><p>x</p><h3 id="b">B</h3><h4>C</h4><h3>D</h3>', $r['html']);
        $this->assertSame(4, $r['changed']);
    }

    public function test_przeskok_poziomu_jest_domykany_a_rodzenstwo_zostaje_rowne(): void
    {
        $r = HeadingStructure::fix('<h2>A</h2><h4>B</h4><h5>C</h5><h4>D</h4><h2>E</h2><h3>F</h3>');

        $this->assertSame('<h2>A</h2><h3>B</h3><h4>C</h4><h3>D</h3><h2>E</h2><h3>F</h3>', $r['html']);
    }

    public function test_puste_naglowki_i_tytul_h0_z_phpword(): void
    {
        $r = HeadingStructure::fix("<h0>Tytuł</h0><h2> &nbsp;</h2><h3><img src=\"a.png\" alt=\"Logo\"></h3><h2><span></span></h2>");

        $this->assertSame('<h2>Tytuł</h2><h3><img src="a.png" alt="Logo"></h3>', $r['html']);
        $this->assertSame(2, $r['removed']);
    }

    public function test_poprawna_struktura_zostaje_bez_zmian(): void
    {
        $html = '<h2>A</h2><p>x</p><h3>B</h3><h2>C</h2>';
        $r = HeadingStructure::fix($html);

        $this->assertSame($html, $r['html']);
        $this->assertSame(0, $r['changed'] + $r['removed']);
    }

    public function test_import_docx_poprawia_naglowki_i_mozna_to_wylaczyc(): void
    {
        config(['app.url' => 'http://localhost', 'editions.forced' => 'pro']);
        Edition::flush();

        $word = new PhpWord();
        $word->addTitleStyle(1, ['bold' => true]);
        $word->addTitleStyle(3, ['bold' => true]);
        $section = $word->addSection();
        $section->addTitle('Rozdział', 1);
        $section->addText('Treść rozdziału.');
        $section->addTitle('Szczegół', 3);
        $path = tempnam(sys_get_temp_dir(), 'docx_test_').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $file = fn () => new UploadedFile($path, 'plik.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $json = $this->actingAs($admin)->post(route('admin.editor.docx.import'), ['docx' => $file()])->assertOk()->json();
        $this->assertMatchesRegularExpression('/<h2[^>]*>.*Rozdział.*<\/h2>/s', $json['html']);
        $this->assertMatchesRegularExpression('/<h3[^>]*>.*Szczegół.*<\/h3>/s', $json['html']);
        $this->assertStringNotContainsString('<h1', $json['html']);
        $this->assertSame(1, $json['headings']['changed']);

        $raw = $this->actingAs($admin)->post(route('admin.editor.docx.import'), ['docx' => $file(), 'fix_headings' => '0'])->assertOk()->json();
        $this->assertMatchesRegularExpression('/<h1[^>]*>.*Rozdział/s', $raw['html']);

        @unlink($path);
        Edition::flush();
    }

    public function test_edytory_maja_przycisk_poprawy_naglowkow(): void
    {
        $classic = file_get_contents(resource_path('views/admin/partials/editor.blade.php'));
        $this->assertStringContainsString('-h-fix', $classic);
        $this->assertStringContainsString('Popraw strukturę nagłówków', $classic);

        $blocks = file_get_contents(resource_path('js/block-editor.js'));
        $this->assertStringContainsString('function fixHeadingStructure()', $blocks);
        $this->assertStringContainsString('json.headings', $blocks);
    }
}
