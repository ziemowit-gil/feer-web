<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Okno „Kto publikuje?” leży w formularzu strony — nie może mieć własnego <form> ani pola required. */
class PageMetkaDialogMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_dialog_does_not_break_page_form(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $shared = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'strona@feer.org.pl']);
        User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'is_published' => false]);

        $html = $this->actingAs($shared)->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();
        $start = strpos($html, 'id="page-edit-form"');
        $dialog = strpos($html, 'id="metka-dialog"');
        $aside = strpos($html, 'aria-label="Publikacja i miejsce strony"');
        $this->assertNotFalse($dialog);
        $between = substr($html, $start, $aside - $start);
        $this->assertStringNotContainsString('<form', $between, 'Brak zagnieżdżonych formularzy przed panelem publikacji.');
        $this->assertStringNotContainsString('</form>', $between);
        $this->assertDoesNotMatchRegularExpression('/id="metka-dialog-user"[^>]*\srequired/', $html);
    }
}
