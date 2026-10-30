<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona „Zadaj pytanie” przy działaniu: opis po lewej, formularz w karcie po prawej, krótki tekst bez zbędnych słów. */
class ProjectAskPageLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_strona_ma_uklad_dwukolumnowy_i_link_powrotny(): void
    {
        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);
        $project = Project::create(['title' => 'Szkolenia testowe', 'slug' => 'szkolenia-testowe', 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true, 'coordinator_name' => 'Ewelina Frączek']);

        $html = $this->get(route('projects.ask', $project))->assertOk()->getContent();

        $this->assertStringContainsString('lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]', $html);
        $this->assertStringContainsString('Wiadomość trafi prosto do osoby kontaktowej: <strong>Ewelina Frączek</strong>', $html);
        $this->assertStringContainsString('Wróć do działania', $html);
        $this->assertStringContainsString('Wyślij pytanie', $html);
        $this->assertStringNotContainsString('bezpośrednio do osoby', $html);
    }
}
