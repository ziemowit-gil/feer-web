<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** Uniwersalna rama <x-proj-frame>: płaska, jasnoszara jak wiersze listy działań, jedno miejsce dla ramek informacyjnych. */
class ProjFrameComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_rama_renderuje_ikone_naglowek_tekst_i_linki(): void
    {
        $html = Blade::render('<x-proj-frame icon="fa-coins" title="Forma płatna" class="mb-4">Tekst <strong>ramy</strong>.<x-slot:links><a href="/x">Link</a></x-slot:links></x-proj-frame>');

        $this->assertStringContainsString('class="proj-frame mb-4"', $html);
        $this->assertStringContainsString('<i class="fa-solid fa-coins"></i>', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html, 'ikona jest dekoracyjna');
        $this->assertStringContainsString('<p class="proj-frame-title">Forma płatna</p>', $html);
        $this->assertStringContainsString('Tekst <strong>ramy</strong>.', $html);
        $this->assertStringContainsString('<div class="proj-frame-links"><a href="/x">Link</a></div>', $html);
    }

    public function test_rama_bez_naglowka_i_linkow_oraz_w_tonie_brand(): void
    {
        $html = Blade::render('<x-proj-frame tone="brand">Sama treść.</x-proj-frame>');

        $this->assertStringContainsString('class="proj-frame proj-frame--brand"', $html);
        $this->assertStringContainsString('fa-circle-info', $html, 'domyślna ikona');
        $this->assertStringNotContainsString('<p class="proj-frame-title">', $html);
        $this->assertStringNotContainsString('<div class="proj-frame-links">', $html);
    }

    public function test_usluga_odplatna_i_forma_platna_uzywaja_wspolnej_ramy(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['projects_subprojects_enabled' => true]);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);
        $offer = Project::create(['title' => 'Usługa testowa', 'slug' => 'usluga-testowa', 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true, 'kind' => 'paid_offer']);
        $child = Project::create(['title' => 'Forma płatna', 'slug' => 'forma-platna', 'category_id' => $category->id, 'content' => '<p>x</p>', 'is_published' => true, 'is_paid' => true, 'is_offered' => true, 'parent_id' => $offer->id]);

        $offerHtml = $this->get(route('projects.show', $offer))->assertOk()->getContent();
        $this->assertStringContainsString('class="proj-frame"', $offerHtml);
        $this->assertStringContainsString('Część kosztów pokrywa <strong>Beneficjent</strong>, czyli Ty lub osoba, która korzysta z usługi', $offerHtml);
        $this->assertStringContainsString('Cenę i zasady znajdziesz poniżej', $offerHtml);
        $this->assertStringNotContainsString('class="proj-note"', $offerHtml, 'stara ramka nie jest już używana');

        $childHtml = $this->get(route('projects.show', $child))->assertOk()->getContent();
        $this->assertStringContainsString('Ta forma jest <strong>płatna</strong>. Część kosztów pokrywa <strong>Beneficjent</strong>', $childHtml);
    }

    public function test_tryb_wysokiego_kontrastu_obejmuje_rame(): void
    {
        $this->assertStringContainsString('.proj-frame,', file_get_contents(resource_path('views/layouts/site.blade.php')));
    }
}
