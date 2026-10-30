<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Database\Seeders\ProjectWhyTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectWhyTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug, array $extra): Project
    {
        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);

        return Project::withoutGlobalScopes()->updateOrCreate(['slug' => $slug], array_merge([
            'title' => $slug, 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true,
        ], $extra));
    }

    public function test_poprawia_pole_dlaczego_to_robimy_i_jest_idempotentny(): void
    {
        $n = "\r\n";
        $p = $this->project('materialy-edukacyjne', [
            'why' => "Wierzymy, że każda zmiana zaczyna się od edukacji. Świat staje się bardziej otwarty wtedy, gdy uczymy się rozumieć siebie nawzajem – już od najmłodszych lat.{$n}{$n}Wiemy, że stereotypy i uprzedzenia najczęściej biorą się z niewiedzy. Dlatego chcemy dawać dzieciom, młodzieży i dorosłym prostą, dobrą wiedzę – taką, która uczy szacunku, ciekawości i akceptacji.{$n}{$n}Marzy nam się świat, w którym osoby z niepełnosprawnościami są traktowane naturalnie – bez litości, bez strachu, bez dystansu. Świat, w którym różnorodność nie dzieli, tylko łączy.{$n}{$n}Dlatego działamy. Krok po kroku, lekcja po lekcji, rozmowa po rozmowie – budujemy razem bardziej otwarte społeczeństwo.",
        ]);
        $own = $this->project('edukacja-w-social-media', ['why' => 'Własny tekst redakcji.']);

        $this->seed(ProjectWhyTextsHumanizedSeeder::class);
        $this->seed(ProjectWhyTextsHumanizedSeeder::class);

        $this->assertStringStartsWith('Każda zmiana zaczyna się od edukacji.', $p->fresh()->why);
        $this->assertStringNotContainsString('—', $p->fresh()->why);
        $this->assertStringNotContainsString('–', $p->fresh()->why);
        $this->assertStringNotContainsString('Dlatego działamy', $p->fresh()->why);
        $this->assertSame('Własny tekst redakcji.', $own->fresh()->why);
    }

    public function test_poprawia_sekcje_partnerstwa_i_opis_meta(): void
    {
        $dwp = $this->project('dostepnosc-w-praktyce', ['custom_sections' => [['title' => 'Współpraca tylko w ramach partnerstw', 'featured' => true,
            'content' => '<p>To działanie realizujemy wyłącznie w ramach partnerstw. Nie prowadzimy otwartego naboru — współpracę nawiązujemy bezpośrednio, na podstawie wzajemnego zaufania i dopasowania do misji fundacji. Jeśli chcesz nawiązać kontakt, napisz do nas.</p>']]]);
        $ngo = $this->project('wsparcie-ngo', ['meta_description' => 'Wsparcie technologiczne dla NGO: Microsoft 365 i Google Workspace, strony WWW, bezpieczeństwo, hosting i dostępność WCAG — w stałym, miesięcznym abonamencie.']);

        $this->seed(ProjectWhyTextsHumanizedSeeder::class);

        $this->assertStringContainsString('Nie prowadzimy otwartego naboru. Współpracę nawiązujemy', $dwp->fresh()->custom_sections[0]['content']);
        $this->assertTrue($dwp->fresh()->custom_sections[0]['featured']);
        $this->assertStringNotContainsString('—', $ngo->fresh()->meta_description);
    }
}
