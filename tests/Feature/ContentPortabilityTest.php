<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ContentPortability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPortabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_content_tables_exclude_auth_and_framework(): void
    {
        $tables = app(ContentPortability::class)->contentTables();

        foreach (['pages', 'nav_items', 'site_settings', 'volunteer_ads'] as $expected) {
            $this->assertContains($expected, $tables);
        }
        foreach (['users', 'user_groups', 'sessions', 'migrations', 'meeting_signups', 'accessibility_reports'] as $excluded) {
            $this->assertNotContains($excluded, $tables);
        }
    }

    public function test_export_then_import_restores_content_without_deleting_new_rows(): void
    {
        $portability = app(ContentPortability::class);

        SiteSetting::current()->update(['site_name' => 'FEER Oryginał']);
        $page = Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'page', 'is_published' => true]);

        $zip = storage_path('app/test-content-export.zip');
        $portability->export($zip);

        // Symuluj „nowy hosting": zmieniamy ustawienia, kasujemy stronę,
        // dokładamy nową stronę (której nie ma w paczce).
        SiteSetting::current()->update(['site_name' => 'Coś innego']);
        $page->delete();
        $extra = Page::create(['title' => 'Nowa', 'slug' => 'nowa', 'type' => 'page', 'is_published' => true]);

        $summary = $portability->import($zip);

        // Treść z paczki wróciła…
        $this->assertArrayHasKey('pages', $summary);
        $this->assertSame('FEER Oryginał', SiteSetting::current()->fresh()->site_name);
        $this->assertDatabaseHas('pages', ['slug' => 'oferta']);
        // …a wiersz dodany po eksporcie NIE został skasowany.
        $this->assertDatabaseHas('pages', ['id' => $extra->id, 'slug' => 'nowa']);

        @unlink($zip);
    }
}
