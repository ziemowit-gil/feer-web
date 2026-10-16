<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Models\NewsletterImport;
use Tests\TestCase;
use ZipArchive;

/** Import subskrybentów z arkusza Excel (.xlsx) i OpenDocument (.ods) — bez zewnętrznych bibliotek. */
class NewsletterSpreadsheetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        Storage::fake('local');
        Bus::fake([SendDoubleOptIn::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $data = (string) file_get_contents($path);
        unlink($path);

        return $data;
    }

    /** Arkusz jak z Excela: współdzielone teksty, tekst w komórce, data jako liczba, pusta kolumna, arkusz pod inną nazwą. */
    private function xlsx(): string
    {
        $shared = '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<si><t>E-mail</t></si><si><t>Imię</t></si><si><t>Nazwisko</t></si><si><t>Data zapisu</t></si><si><t>Status</t></si>'
            .'<si><t>ola@example.org</t></si><si><r><t>Ol</t></r><r><t>a</t></r></si><si><t>Kowalska</t></si><si><t>active</t></si></sst>';
        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            .'<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c><c r="F1" t="s"><v>4</v></c></row>'
            .'<row r="2"><c r="A2" t="s"><v>5</v></c><c r="B2" t="s"><v>6</v></c><c r="C2" t="s"><v>7</v></c><c r="D2"><v>45352.5</v></c><c r="F2" t="s"><v>8</v></c></row>'
            .'<row r="3"><c r="A3" t="inlineStr"><is><t>piotr@example.org</t></is></c><c r="B3" t="inlineStr"><is><t>Piotr</t></is></c><c r="F3" t="inlineStr"><is><t>unsubscribed</t></is></c></row>'
            .'<row r="4"></row>'
            .'</sheetData></worksheet>';

        return $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Subskrybenci" sheetId="1" r:id="rId3"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/dane.xml"/></Relationships>',
            'xl/sharedStrings.xml' => $shared,
            'xl/worksheets/dane.xml' => $sheet,
        ]);
    }

    private function ods(): string
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><office:body><office:spreadsheet>'
            .'<table:table table:name="Arkusz1">'
            .'<table:table-row><table:table-cell office:value-type="string"><text:p>email</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>subscribed_at</text:p></table:table-cell><table:table-cell table:number-columns-repeated="1021"/></table:table-row>'
            .'<table:table-row><table:table-cell office:value-type="string"><text:p>ewa@example.org</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>Ewa</text:p></table:table-cell><table:table-cell office:value-type="date" office:date-value="2023-06-15T08:00:00"><text:p>15.06.2023</text:p></table:table-cell><table:table-cell table:number-columns-repeated="1021"/></table:table-row>'
            .'<table:table-row table:number-rows-repeated="1048000"><table:table-cell table:number-columns-repeated="1024"/></table:table-row>'
            .'</table:table><table:table table:name="Arkusz2"><table:table-row><table:table-cell office:value-type="string"><text:p>inny@example.org</text:p></table:table-cell></table:table-row></table:table>'
            .'</office:spreadsheet></office:body></office:document-content>';

        return $this->zip(['mimetype' => 'application/vnd.oasis.opendocument.spreadsheet', 'content.xml' => $content]);
    }

    public function test_import_z_excela_xlsx(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('subskrybenci.xlsx', $this->xlsx()),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $import = NewsletterImport::latest('id')->first();

        $this->assertSame('subskrybenci.xlsx', $import->file_name);
        $this->assertSame(['E-mail', 'Imię', 'Nazwisko', 'Data zapisu', '', 'Status'], $import->columns);
        $this->assertSame(['email' => 0, 'name' => 1, 'last_name' => 2, 'status' => 5, 'subscribed_at' => 3], $import->mapping);
        $this->assertSame([Subscriber::STATUS_CONFIRMED => 1, Subscriber::STATUS_UNSUBSCRIBED => 1], $import->summary['by_status']);
        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import.show', $import))->assertOk()->assertSee('ola@example.org');

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), [
            'action' => 'run', 'mapping' => $import->mapping, 'has_consent' => 1, 'consent_source' => 'arkusz biura',
        ])->assertSessionHasNoErrors();

        $ola = Subscriber::findByEmail('ola@example.org');
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $ola->status);
        $this->assertSame('Ola Kowalska', $ola->name);
        $this->assertSame('2024-03-01 12:00', $ola->confirmed_at->format('Y-m-d H:i'), 'data Excela (liczba dni) zamieniona na datę');
        $this->assertSame(Subscriber::STATUS_UNSUBSCRIBED, Subscriber::findByEmail('piotr@example.org')->status);
    }

    public function test_import_z_arkusza_ods_tylko_pierwszy_arkusz(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('lista.ods', $this->ods()),
        ])->assertSessionHasNoErrors();
        $import = NewsletterImport::latest('id')->first();

        $this->assertSame(['email', 'name', 'subscribed_at'], $import->columns);
        $this->assertSame(1, $import->summary['total']);

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), [
            'action' => 'run', 'mapping' => $import->mapping, 'default_status' => 'pending',
        ])->assertSessionHasNoErrors();

        $ewa = Subscriber::findByEmail('ewa@example.org');
        $this->assertSame(Subscriber::STATUS_PENDING, $ewa->status);
        $this->assertSame('Ewa', $ewa->name);
        $this->assertNull(Subscriber::findByEmail('inny@example.org'));
        Bus::assertDispatched(SendDoubleOptIn::class, 1);
    }

    public function test_stary_format_xls_i_uszkodzony_plik_daja_czytelny_blad(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->from(route('admin.newsletter.subskrybenci.import'))->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('stary.xls', 'binarne dane BIFF'),
        ])->assertSessionHasErrors(['file' => 'Obsługujemy pliki .xlsx, .ods, .csv i .json. Starszy format .xls zapisz w Excelu jako .xlsx (Plik → Zapisz jako).']);

        $this->actingAs($admin)->from(route('admin.newsletter.subskrybenci.import'))->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('zepsuty.xlsx', 'to nie jest zip'),
        ])->assertSessionHasErrors('file');
        $this->assertSame(0, NewsletterImport::count());
    }
}
