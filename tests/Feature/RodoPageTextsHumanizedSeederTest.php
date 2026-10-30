<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Page;
use Database\Seeders\RodoPageTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RodoPageTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_poprawia_tytul_i_etykiety_plikow_oraz_jest_idempotentny(): void
    {
        $page = Page::withoutGlobalScopes()->updateOrCreate(['slug' => 'rodo'], ['title' => 'Dane osobowe - klauzule informacyjne', 'type' => 'standard', 'is_published' => true, 'content' => '<p>[klauzule-rodo]</p>']);
        $file = $page->attachments()->create(['label' => 'Klauzula informacyjna - formularz kontaktowy']);
        $own = $page->attachments()->create(['label' => 'Własna etykieta - bez zmian']);

        $this->seed(RodoPageTextsHumanizedSeeder::class);
        $this->seed(RodoPageTextsHumanizedSeeder::class);

        $this->assertSame('Dane osobowe: klauzule informacyjne', $page->fresh()->title);
        $this->assertSame('Klauzula informacyjna: formularz kontaktowy', Attachment::find($file->id)->label);
        $this->assertSame('Własna etykieta - bez zmian', Attachment::find($own->id)->label);
        $this->assertTrue($page->fresh()->layout_data['hide_attachments'], 'lista plików ukryta na stronie RODO');
    }

    public function test_ukryta_lista_plikow_nie_pokazuje_sie_na_stronie_a_pliki_zostaja(): void
    {
        $page = Page::withoutGlobalScopes()->updateOrCreate(['slug' => 'rodo'], ['title' => 'Dane osobowe', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Treść.</p>']);
        $page->attachments()->create(['label' => 'Klauzula do pobrania']);

        $html = view('partials.attachments-list', ['page' => $page->fresh(), 'attachments' => $page->fresh()->attachments])->render();
        $this->assertStringContainsString('Pliki do pobrania', $html);

        $page->update(['layout_data' => ['hide_attachments' => true]]);
        $hidden = view('partials.attachments-list', ['page' => $page->fresh(), 'attachments' => $page->fresh()->attachments])->render();
        $this->assertStringNotContainsString('Pliki do pobrania', $hidden);
        $this->assertSame(1, $page->attachments()->count(), 'pliki nie są usuwane');
    }
}
