<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Treść w blokach: formularz klasyczny, szybka edycja i edycja na żywo nie mogą jej nadpisać. */
class BlockContentLockTest extends TestCase
{
    use RefreshDatabase;

    private const BLOCKS = [['type' => 'paragraph', 'attrs' => ['text' => 'Z bloków']]];

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'default_editor' => User::EDITOR_CLASSIC]);
    }

    private function blockPage(): Page
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'content' => '<p>Z bloków</p>', 'is_published' => true]);
        $page->forceFill(['blocks' => self::BLOCKS])->saveQuietly();

        return $page->refresh();
    }

    public function test_form_shows_read_only_preview_instead_of_editor(): void
    {
        $page = $this->blockPage();

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();

        $this->assertStringContainsString('Zwykła edycja jest zablokowana', $html);
        $this->assertStringContainsString('Edytuj treść w edytorze blokowym', $html);
        $this->assertStringNotContainsString('name="content"', $html);
    }

    public function test_form_without_blocks_keeps_classic_editor(): void
    {
        $page = Page::create(['title' => 'Zwykła', 'slug' => 'zwykla', 'type' => 'standard', 'content' => '<p>x</p>', 'is_published' => true]);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();

        $this->assertStringContainsString('name="content"', $html);
        $this->assertStringNotContainsString('Zwykła edycja jest zablokowana', $html);
    }

    public function test_news_quick_update_ignores_content_when_blocks_exist(): void
    {
        $news = News::create(['title' => 'A', 'slug' => 'a', 'content' => '<p>Z bloków</p>', 'is_published' => true]);
        $news->forceFill(['blocks' => self::BLOCKS])->saveQuietly();

        $this->actingAs($this->admin())->patchJson(route('admin.newsy.szybka-edycja', $news), [
            'title' => 'B', 'excerpt' => null, 'is_published' => true, 'content' => '<p>Nadpisane</p>',
        ])->assertOk();

        $news->refresh();
        $this->assertSame('B', $news->title);
        $this->assertSame('<p>Z bloków</p>', $news->content);
        $this->assertIsArray($news->blocks);
    }

    public function test_inline_edit_rejects_content_when_blocks_exist(): void
    {
        $page = $this->blockPage();

        $this->actingAs($this->admin())->putJson(route('admin.inline-edit.update'), [
            'model' => 'page', 'id' => $page->id, 'field' => 'content', 'value' => '<p>Nadpisane</p>',
        ])->assertStatus(422);

        $this->assertSame('<p>Z bloków</p>', $page->refresh()->content);
        $this->assertIsArray($page->blocks);
    }

    public function test_unlocked_again_when_block_editor_disabled(): void
    {
        $page = $this->blockPage();
        SiteSetting::current()->update(['disabled_modules' => ['block_editor']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();

        $this->assertStringContainsString('name="content"', $html);
    }
}
