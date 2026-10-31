<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Database\Seeders\SzkoleniaInstitutionPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SzkoleniaInstitutionPagesSeederTest extends TestCase
{
    use RefreshDatabase;

    private function subSite(): SiteSetting
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $main = SiteSetting::current();

        return SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $main->site_template,
        ]);
    }

    public function test_tworzy_szkice_stron_dla_instytucji_w_witrynie_szkolenia_i_jest_idempotentny(): void
    {
        $site = $this->subSite();

        $this->seed(SzkoleniaInstitutionPagesSeeder::class);
        $this->seed(SzkoleniaInstitutionPagesSeeder::class);

        $pages = Page::withoutGlobalScopes()->where('site_id', $site->id)->get();
        $this->assertCount(4, $pages, 'strona nadrzędna i trzy podstrony, bez duplikatów');
        $this->assertTrue($pages->every(fn ($p) => ! $p->is_published), 'wszystko to szkice');

        $parent = $pages->firstWhere('title', 'Dla instytucji i firm szkoleniowych');
        $this->assertNull($parent->parent_id);
        $this->assertSame(
            ['Jak wygląda współpraca', 'Szkolenia dla instytucji', 'Współpraca z firmami szkoleniowymi'],
            $pages->where('parent_id', $parent->id)->pluck('title')->sort()->values()->all(),
        );
    }

    public function test_tresc_nie_ma_nawiasow_szablonowych_myslnikow_ani_cen(): void
    {
        $this->subSite();
        $this->seed(SzkoleniaInstitutionPagesSeeder::class);

        foreach (Page::withoutGlobalScopes()->where('title', '!=', '')->get() as $page) {
            $text = html_entity_decode(strip_tags((string) $page->content));
            $withoutShortcode = str_replace('[podstrony]', '', $text);
            $this->assertStringNotContainsString('[', $withoutShortcode, "bez nawiasów szablonowych: {$page->title}");
            $this->assertStringNotContainsString(' — ', $text, "bez myślników jako spójników: {$page->title}");
            $this->assertDoesNotMatchRegularExpression('/\d\s?zł/u', $text, "bez cen: {$page->title}");
        }
    }

    public function test_bez_witryny_szkolenia_nic_nie_robi(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current();

        $this->seed(SzkoleniaInstitutionPagesSeeder::class);

        $this->assertSame(0, Page::withoutGlobalScopes()->where('title', 'Szkolenia dla instytucji')->count());
    }
}
