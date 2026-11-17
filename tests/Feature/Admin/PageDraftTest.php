<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\PageDraft;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Wersja robocza opublikowanej strony (Workspaces w TYPO3). */
class PageDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        if (! SiteSetting::current()->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony w ustawieniach testowych.');
        }
    }

    private function livePage(): Page
    {
        $blocks = BlockRenderer::normalize([['type' => 'paragraph', 'attrs' => ['text' => 'Stara treść']]]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-robocza', 'type' => 'standard', 'is_published' => true]);
        $page->updateFromBlockEditor(['blocks' => $blocks, 'content' => BlockRenderer::render($blocks)]);

        return $page->fresh();
    }

    private function saveDraft(User $user, Page $page, string $text): void
    {
        $this->actingAs($user)->putJson(route('admin.podstrony.bloki.update', $page).'?robocza=1', [
            'title' => 'Strona nowa', 'meta_description' => null,
            'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => $text]]],
        ])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_zapis_w_trybie_roboczym_nie_zmienia_strony_publicznej(): void
    {
        $page = $this->livePage();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', [$page, 'robocza' => 1]))->assertOk()
            ->assertSee('wersję roboczą', false)->assertSee('?robocza=1', false);
        $this->saveDraft($admin, $page, 'Nowa treść');

        $page->refresh();
        $this->assertStringContainsString('Stara treść', $page->content);
        $this->assertSame('Strona', $page->title);
        $this->assertSame('Strona nowa', $page->draft->title);

        // Publicznie stara treść, podgląd wersji roboczej — nowa
        $this->get('/strona-robocza')->assertOk()->assertSee('Stara treść')->assertDontSee('Nowa treść');
        $this->get($page->draftPreviewUrl())->assertOk()->assertSee('Nowa tre', false)->assertSee('Strona nowa', false);

        // Ponowne otwarcie edytora w trybie roboczym ładuje kopię
        $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', [$page, 'robocza' => 1]))->assertOk()->assertSee('Nowa tre', false)->assertSee('Strona nowa', false);
    }

    public function test_publikacja_przenosi_zmiany_na_strone_i_usuwa_kopie(): void
    {
        $page = $this->livePage();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->saveDraft($admin, $page, 'Nowa treść');

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Wersja robocza zmian', $html);
        $this->assertStringContainsString('Opublikuj zmiany', $html);

        $this->actingAs($admin)->post(route('admin.podstrony.robocza.opublikuj', $page))->assertRedirect()->assertSessionHas('status');
        $page->refresh();
        $this->assertStringContainsString('Nowa treść', $page->content);
        $this->assertSame('Strona nowa', $page->title);
        $this->assertNotNull($page->blocks);
        $this->assertSame(0, PageDraft::count());
    }

    public function test_redaktor_bez_akceptacji_wysyla_do_akceptacji_i_nie_publikuje(): void
    {
        Notification::fake();
        $page = $this->livePage();
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->saveDraft($editor, $page, 'Nowa treść');

        $this->actingAs($editor)->post(route('admin.podstrony.robocza.opublikuj', $page))->assertSessionHas('error');
        $this->assertStringContainsString('Stara treść', $page->fresh()->content);

        $this->actingAs($editor)->post(route('admin.podstrony.robocza.zglos', $page))->assertSessionHas('status');
        $this->assertNotNull($page->draft()->first()->submitted_at);
        Notification::assertSentTimes(\App\Notifications\ContentSubmittedForApproval::class, 1);
    }

    public function test_odrzucenie_usuwa_kopie_bez_zmiany_strony(): void
    {
        $page = $this->livePage();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->saveDraft($admin, $page, 'Nowa treść');

        $this->actingAs($admin)->delete(route('admin.podstrony.robocza.odrzuc', $page))->assertRedirect();
        $this->assertSame(0, PageDraft::count());
        $this->assertStringContainsString('Stara treść', $page->fresh()->content);
    }
}
