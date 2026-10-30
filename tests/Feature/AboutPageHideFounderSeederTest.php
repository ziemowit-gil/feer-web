<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\AboutPageHideFounderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageHideFounderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_ukrywa_slowo_od_fundatora_i_zostawia_pozostale_ukryte_sekcje(): void
    {
        $page = Page::create(['title' => 'Fundacja FEER', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true, 'about_sections_hidden' => ['team']]);

        $this->seed(AboutPageHideFounderSeeder::class);
        $this->seed(AboutPageHideFounderSeeder::class);

        $this->assertSame(['team', 'founder'], $page->fresh()->about_sections_hidden);
        $this->assertNotContains('founder', $page->fresh()->aboutSections(true));
    }
}
