<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\BlockRenderer;
use App\Support\EditorPermissions;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Bloki edytora blokowego zależne od modułów włączonych w Ustawienia → Moduły. */
class BlockEditorModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCache();
        if (! SiteSetting::current()->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony w ustawieniach testowych.');
        }
    }

    private function resetCache(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function disable(array $modules): void
    {
        $site = SiteSetting::current();
        $site->update(['disabled_modules' => array_values(array_unique([...(array) $site->disabled_modules, ...$modules]))]);
        $this->resetCache();
    }

    /** Konfiguracja edytora z atrybutu data-config. @return array<string, mixed> */
    private function editorConfig(User $user, Page $page): array
    {
        $html = $this->actingAs($user)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()->getContent();
        $this->assertSame(1, preg_match("/data-config='([^']*)'/", $html, $m));

        return json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_wylaczony_modul_chowa_swoje_bloki_a_wlaczony_ich_nie_ogranicza(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-moduly', 'type' => 'standard', 'is_published' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $config = $this->editorConfig($admin, $page);
        $this->assertNull($config['permissions']['blocks'], 'wszystkie moduły włączone = bez ograniczeń');
        $this->assertSame([], $config['permissions']['modulesOff']);

        // Materiały to moduł instalowany (włączany globalnie), więc wpis witryny go nie wyłącza — blok zostaje.
        $this->disable(['news', 'banners', 'events']);
        $config = $this->editorConfig($admin, $page);
        $this->assertNotContains('newslist', $config['permissions']['blocks']);
        $this->assertContains('materials', $config['permissions']['blocks']);
        $this->assertContains('paragraph', $config['permissions']['blocks']);
        $this->assertContains('columns', $config['permissions']['blocks']);
        $this->assertSame(['newslist' => 'Aktualności'], $config['permissions']['modulesOff']);
        // Skróty modułów (banery, ramka wydarzenia) chowa pusta lista do wyboru.
        $this->assertSame([], $config['classic']['bannerZones']);
        $this->assertSame([], $config['classic']['banners']);
        $this->assertSame([], $config['classic']['events']);
    }

    public function test_grupa_i_wylaczony_modul_ograniczaja_razem(): void
    {
        $group = UserGroup::create(['name' => 'Redakcja', 'modules' => ['pages'], 'block_types' => ['paragraph', 'newslist']]);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'user_group_id' => $group->id]);

        $this->assertSame(['paragraph', 'newslist'], EditorPermissions::allowedBlockTypes($user));
        $this->disable(['news']);
        $this->assertSame(['paragraph'], EditorPermissions::allowedBlockTypes($user->fresh()));
        $this->assertNotContains('newslist', EditorPermissions::allowedBlockTypes(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR])));
    }

    public function test_serwer_nie_przyjmuje_nowego_bloku_wylaczonego_modulu_a_istniejacy_zostaje(): void
    {
        $blocks = BlockRenderer::normalize([['type' => 'newslist', 'attrs' => ['limit' => 3]]]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-aktualnosci', 'type' => 'standard', 'is_published' => true, 'blocks' => $blocks, 'content' => BlockRenderer::render($blocks)]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->disable(['news']);

        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Strona', 'meta_description' => null,
            'blocks' => [
                ['type' => 'paragraph', 'attrs' => ['text' => 'Wstęp']],
                $blocks[0],
                ['type' => 'newslist', 'attrs' => ['limit' => 8]],
            ],
        ])->assertOk();

        $saved = $page->fresh()->blocks;
        $this->assertSame(['paragraph', 'newslist'], array_column($saved, 'type'), 'nowa lista aktualności odrzucona, istniejąca zostaje');
        $this->assertSame($blocks[0]['attrs'], $saved[1]['attrs']);
    }

    public function test_wstawki_wylaczonych_modulow_sa_puste_na_stronie(): void
    {
        \App\Models\News::create(['title' => 'Aktualność testowa', 'slug' => 'aktualnosc-testowa', 'content' => '<p>x</p>', 'published_at' => now()->subDay(), 'is_published' => true]);
        $this->assertStringContainsString('Aktualność testowa', ShortcodeParser::render('<p>[aktualnosci]</p>'));

        $this->disable(['news']);
        $this->assertSame('', trim(ShortcodeParser::render('<p>[aktualnosci]</p>')));
    }
}
