<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\FeerIsModulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeerIsModulesSeederTest extends TestCase
{
    use RefreshDatabase;

    private function site(string $name, string $slug, array $extra = []): SiteSetting
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        return SiteSetting::create($extra + [
            'site_name' => $name, 'slug' => $slug, 'parent_site_id' => SiteSetting::current()->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is',
        ]);
    }

    public function test_wylacza_niepotrzebne_moduly_w_witrynie_wskazanej_nazwa_i_nie_rusza_pozostalych(): void
    {
        $test2 = $this->site('test2', 'inny-slug', ['disabled_modules' => ['podcasts']]);
        $other = $this->site('Inna', 'inna');

        putenv('SITE_SLUG=test2');
        try {
            $this->seed(FeerIsModulesSeeder::class);
            $this->seed(FeerIsModulesSeeder::class);
        } finally {
            putenv('SITE_SLUG');
        }

        $disabled = $test2->fresh()->disabled_modules;
        $this->assertContains('podcasts', $disabled, 'wcześniej wyłączone zostają');
        $this->assertSame(count(array_unique($disabled)), count($disabled), 'bez duplikatów po ponownym uruchomieniu');
        foreach (SiteSetting::FEER_IS_DISABLED_MODULES as $module) {
            $this->assertFalse($test2->fresh()->isModuleEnabled($module), $module);
        }
        foreach (['pages', 'news', 'events', 'forms', 'hero', 'faq', 'partners'] as $kept) {
            $this->assertTrue($test2->fresh()->isModuleEnabled($kept), $kept);
        }
        $this->assertTrue($other->fresh()->isModuleEnabled('projects'), 'inna witryna nietknięta');
    }
}
