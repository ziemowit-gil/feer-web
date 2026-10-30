<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use Database\Seeders\WeCmsTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeCmsTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $slug, string $content, array $extra = []): Page
    {
        return Page::withoutGlobalScopes()->updateOrCreate(['slug' => $slug], $extra + ['title' => $slug, 'type' => 'standard', 'is_published' => true, 'content' => $content]);
    }

    public function test_poprawia_podstrony_wecms_i_jest_idempotentny(): void
    {
        $ocms = $this->page('wecms-o-cms', '<p><strong>Ta strona działa na weCMS</strong> — wszystko tu widać.</p><ul><li><strong>Czysty design</strong> — płaski układ.</li></ul><figure><figcaption>Panel redakcji — pulpit</figcaption></figure>');
        $dost = $this->page('wecms-funkcje-dostepnosc', '<p>Dostępność jest wbudowana w system, a nie dodawana na końcu.</p>');
        $own = $this->page('wecms-demo', '<p>Własny tekst redakcji — bez zmian.</p>');

        $this->seed(WeCmsTextsHumanizedSeeder::class);
        $this->seed(WeCmsTextsHumanizedSeeder::class);

        $this->assertStringNotContainsString('—', $ocms->fresh()->content);
        $this->assertStringContainsString('<strong>Czysty design</strong>: płaski układ.', $ocms->fresh()->content);
        $this->assertStringContainsString('<figcaption>Panel redakcji: pulpit', $ocms->fresh()->content);
        $this->assertSame('<p>Dostępność jest wbudowana w system od początku.</p>', $dost->fresh()->content);
        $this->assertStringContainsString('Własny tekst redakcji', $own->fresh()->content);
    }

    public function test_poprawia_strone_z_blokami_i_tytuly_dzialan(): void
    {
        $strony = $this->page('wecms-funkcje-strony', '<p>stare</p>', ['blocks' => [
            ['type' => 'html', 'attrs' => ['html' => '<ul><li>Publikacja z wyprzedzeniem — strona pojawia się sama.</li></ul>']],
        ]]);
        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);
        $project = Project::withoutGlobalScopes()->updateOrCreate(['slug' => 'wecms'], ['title' => 'weCMS — system do stron organizacji społecznych', 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true]);

        $this->seed(WeCmsTextsHumanizedSeeder::class);

        $this->assertStringContainsString('Publikacja z wyprzedzeniem: strona pojawia się sama.', $strony->fresh()->content);
        $this->assertStringContainsString('Publikacja z wyprzedzeniem: strona pojawia', $strony->fresh()->blocks[0]['attrs']['html']);
        $this->assertSame('weCMS: system do stron organizacji społecznych', $project->fresh()->title);
    }
}
