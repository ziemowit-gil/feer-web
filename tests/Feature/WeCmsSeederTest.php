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
        $form = \App\Models\FormDefinition::where('slug', WeCmsSeeder::FORM_SLUG)->sole();
        $this->assertSame('ziemowit.gil@feer.org.pl', $form->settings['notification_email']);
        $this->assertTrue($form->is_active);
        $this->assertSame(['O CMS', 'Funkcjonalności', 'Wersje', 'Demo', 'Wymagania', 'Zamówienie'], $project->pageTree(true)->pluck('title')->all());
        $this->assertSame(6, \App\Models\Page::where('project_id', $project->id)->count());
        $features = $project->pageTree(true)->firstWhere('title', 'Funkcjonalności');
        $this->assertSame(['Strony i struktura', 'Edytory i redakcja', 'Praca zespołowa i bezpieczeństwo', 'Aktualności', 'Moduły', 'Dostępność', 'Wygląd i nawigacja', 'Integracje i zaplecze'],
            $features->tree_children->pluck('title')->all());

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertStringNotContainsString('class="proj-cta"', $html, 'bez przycisku w nagłówku');
        $this->assertStringNotContainsString('Szczegóły już wkrótce.</p>', $html, 'bez zajawki w nagłówku');
        $this->assertStringNotContainsString('Dla kogo:', $html);
        // Elementy tylko dla FEER nie są opisywane jako część weCMS.
        foreach (['Oferty pracy:', 'Mapa pomocy', 'Sprawozdania:', 'Wydarzenia:', 'Materiały edukacyjne:', 'Działania i projekty', 'storage/'] as $feerOnly) {
            $this->assertStringNotContainsString($feerOnly, $html);
        }
        foreach (['weCMS — system do stron organizacji społecznych', 'mieliśmy dość WordPressa', 'czysty, płaski design', 'prawie zerowe koszty', 'Szczegóły już wkrótce', 'Ta strona — feer.org.pl — działa na weCMS', 'Ta strona działa na weCMS', 'PHP 8.4', 'Porównanie wersji weCMS', 'od maja do października 2026', 'Wersja demonstracyjna weCMS będzie dostępna niebawem', 'Wersja weCMS', 'Szablon piszemy indywidualnie dla każdej organizacji.', 'będziemy go dalej rozwijać', 'Zapytanie ofertowe:', 'YubiKey', 'Kreator formularzy:', 'tryb czarno-żółty', 'system do zarządzania treścią', 'piszemy indywidualnie dla każdej organizacji', 'Gdy licencja wygaśnie', 'Przelewy24', 'aria-label="Sekcje działania"'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
