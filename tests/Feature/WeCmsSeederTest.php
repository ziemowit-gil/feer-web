<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\WeCmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeCmsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tworzy_zapowiedz_dzialania_wecms(): void
    {
        $this->seed(WeCmsSeeder::class);
        $this->seed(WeCmsSeeder::class);

        $project = Project::where('slug', 'wecms')->sole();
        $this->assertSame('wsparcie-dla-organizacji', $project->category->slug);
        $this->assertFalse($project->is_paid);
        $this->assertSame(['O CMS', 'Funkcjonalności', 'Wersje', 'Wymagania'], $project->pageTree(true)->pluck('title')->all());
        $this->assertSame(4, \App\Models\Page::where('project_id', $project->id)->count());
        $features = $project->pageTree(true)->firstWhere('title', 'Funkcjonalności');
        $this->assertSame(['Strony i struktura', 'Edytory i redakcja', 'Praca zespołowa i bezpieczeństwo', 'Aktualności', 'Działania i projekty', 'Moduły', 'Dostępność', 'Wygląd i nawigacja', 'Integracje i zaplecze'],
            $features->tree_children->pluck('title')->all());

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();
        foreach (['weCMS — system do stron organizacji społecznych', 'mieliśmy dość WordPressa', 'czysty, płaski design', 'prawie zerowe koszty', 'Szczegóły już wkrótce', 'Ta strona — feer.org.pl — działa na weCMS', 'Ta strona działa na weCMS', 'PHP 8.4', 'Porównanie wersji weCMS', 'Zapytanie ofertowe:', 'YubiKey', 'Kreator formularzy:', 'tryb czarno-żółty', 'system do zarządzania treścią', 'piszemy indywidualnie dla każdej organizacji', 'Gdy licencja wygaśnie', 'Przelewy24', 'aria-label="Sekcje działania"'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
