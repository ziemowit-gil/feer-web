<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Menedżer plików: pobieranie pojedynczego pliku i rodzaj pliku obok nazwy (kafelki, lista, panel szczegółów). */
class MediaManagerDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_kafelki_lista_i_panel_maja_pobieranie_i_rodzaj_obok_nazwy(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $media = SiteSetting::current()->addMedia(UploadedFile::fake()->create('klauzula.pdf', 20, 'application/pdf'))->toMediaCollection('documents_test');

        $html = $this->actingAs($admin)->get(route('admin.multimedia.index'))->assertOk()->getContent();

        $this->assertStringContainsString('(PDF)</span>', $html, 'rodzaj pliku obok nazwy');
        $this->assertMatchesRegularExpression('#<a href="[^"]*klauzula\.pdf" download#', $html, 'pobieranie jednego pliku');
        $this->assertStringContainsString('plik: klauzula.pdf (PDF,', $html);

        $panel = $this->actingAs($admin)->get(route('admin.multimedia.panel', $media))->assertOk()->getContent();
        $this->assertStringContainsString('Pobierz ten plik', $panel);
        $this->assertMatchesRegularExpression('#<a href="[^"]*klauzula\.pdf" download#', $panel);
    }
}
