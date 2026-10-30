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
    }
}
