<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Database\Seeders\ProjectTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug, string $content, array $extra = []): Project
    {
        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);

        return Project::create(array_merge([
            'title' => $slug, 'slug' => $slug, 'category_id' => $category->id, 'content' => $content, 'is_published' => true,
        ], $extra));
    }

    public function test_poprawia_tresc_i_jest_idempotentny(): void
    {
        $akademia = $this->project('akademia-dostepnosci-w-ngo', '<p>Powstał po to, żeby dostępność przestała być stresem &mdash; i stała się czymś.</p>');

        $this->seed(ProjectTextsHumanizedSeeder::class);
        $this->seed(ProjectTextsHumanizedSeeder::class);

        $text = strip_tags($akademia->fresh()->content);
        $this->assertStringContainsString('Ma pomóc NGO w sprawach dostępności', $text);
        $this->assertStringNotContainsString('—', $text);
        $this->assertStringNotContainsString('przestała', $text);
    }

    public function test_dzialanie_z_blokami_dostaje_nowe_bloki_i_tresc(): void
    {
        $edu = $this->project('edukacja-w-social-media', '<p>To właśnie tam trafia informacja.</p>', [
            'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'To właśnie tam trafia informacja.', 'align' => 'left', 'lead' => false]]],
        ]);

        $this->seed(ProjectTextsHumanizedSeeder::class);

        $edu->refresh();
        $this->assertNotNull($edu->blocks);
        $this->assertCount(2, $edu->blocks);
        $this->assertStringContainsString('Edukujemy przystępnie', $edu->content);
        $this->assertStringNotContainsString('To właśnie tam', $edu->content);
    }

    public function test_nie_nadpisuje_tresci_zmienionej_pozniej_w_panelu(): void
    {
        $ngo = $this->project('wsparcie-ngo', '<p>Własny tekst redakcji.</p>');

        $this->seed(ProjectTextsHumanizedSeeder::class);

        $this->assertSame('<p>Własny tekst redakcji.</p>', $ngo->fresh()->content);
    }

    public function test_zajawka_szkolen_bez_myslnika(): void
    {
        $s = $this->project('szkolenia-informatyka-programowanie-tyfloinformatyka', '<p>Naszym celem jest rozwijanie logicznego myślenia.</p>', ['excerpt' => 'stary — tekst']);

        $this->seed(ProjectTextsHumanizedSeeder::class);

        $this->assertStringNotContainsString('—', $s->fresh()->excerpt);
        $this->assertStringContainsString('także niewidomych i słabowidzących', $s->fresh()->excerpt);
    }

    public function test_wymuszenie_nadpisuje_tresc_zmieniona_w_panelu(): void
    {
        putenv('PROJECT_TEXTS_FORCE=1');
        $_ENV['PROJECT_TEXTS_FORCE'] = '1';

        try {
            $ngo = $this->project('wsparcie-ngo', '<p>Własny tekst redakcji.</p>');

            $this->seed(ProjectTextsHumanizedSeeder::class);

            $this->assertStringContainsString('Sprawy techniczne nie powinny', $ngo->fresh()->content);
        } finally {
            putenv('PROJECT_TEXTS_FORCE');
            unset($_ENV['PROJECT_TEXTS_FORCE']);
        }
    }
}
