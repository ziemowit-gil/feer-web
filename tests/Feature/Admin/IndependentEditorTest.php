<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\Page;
use App\Models\PageDraft;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\ContentSubmittedForApproval;
use App\Support\AdminMenu;
use App\Support\BlockRenderer;
use App\Support\NewsletterPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Rola „Samodzielny edytor”: uprawnienia Edytora treści, publikacja bez akceptacji,
 * bez kolejki „Do zatwierdzenia” i bez uprawnień administracyjnych.
 */
class IndependentEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function independent(): User
    {
        return User::factory()->create(['role' => User::ROLE_INDEPENDENT_EDITOR]);
    }

    private function newsPayload(array $extra = []): array
    {
        return $extra + ['title' => 'Komunikat', 'published_at' => now()->format('Y-m-d H:i'), 'is_published' => '1', 'content' => '<p>x</p>', 'post_type' => 'standard'];
    }

    public function test_role_mozna_nadac_w_edycji_uzytkownika(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $osrodek = Page::create(['title' => 'Ośrodek', 'slug' => 'osrodek', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs($admin)->get(route('admin.uzytkownicy.edit', $user))->assertOk()
            ->assertSee('<option value="independent_editor"', false)
            ->assertSee('Samodzielny edytor')
            ->assertSee('publikuje bez zatwierdzania');

        $this->actingAs($admin)->put(route('admin.uzytkownicy.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => User::ROLE_INDEPENDENT_EDITOR, 'page_mounts' => [$osrodek->id],
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame(User::ROLE_INDEPENDENT_EDITOR, $user->role);
        $this->assertSame([$osrodek->id], $user->page_mounts, 'gałęzie stron działają jak u Edytora treści');
        $this->actingAs($admin)->get(route('admin.uzytkownicy.index'))->assertOk()->assertSee('Samodzielny edytor');
    }

    public function test_ma_moduly_edytora_tresci_ale_nie_zatwierdza_i_nie_administruje(): void
    {
        $user = $this->independent();
        $contentEditor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        foreach (array_keys(SiteSetting::MODULES) as $module) {
            $this->assertSame($contentEditor->canAccessModule($module), $user->canAccessModule($module), $module);
        }
        $this->assertSame(NewsletterPermissions::for($contentEditor), NewsletterPermissions::for($user));
        $this->assertTrue($user->publishesWithoutApproval());
        $this->assertFalse($user->canApproveContent());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($contentEditor->publishesWithoutApproval(), 'Edytor treści dalej publikuje po akceptacji');
        $this->assertNotContains($user->id, User::approvers()->pluck('id')->all(), 'nie dostaje powiadomień o zgłoszeniach');

        $this->actingAs($user)->get(route('admin.zatwierdzanie.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.uzytkownicy.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.ustawienia.edit'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.witryny.index'))->assertForbidden();

        // Menu jak u Edytora treści, bez „Do zatwierdzenia”, Ustawień i Systemu.
        $keys = function (User $u): array {
            $this->actingAs($u);

            return collect(AdminMenu::for($u->fresh()))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();
        };
        $this->assertSame($keys($contentEditor), $keys($user));
        $this->assertNotContains('approvals', $keys($user));
        $this->assertContains('approvals', $keys(User::factory()->create(['role' => User::ROLE_ADMIN])));
    }

    public function test_aktualnosc_publikuje_od_razu_a_edytor_tresci_dalej_zglasza(): void
    {
        $this->useClassicNewsForm();
        Notification::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($this->independent())->post(route('admin.newsy.store'), $this->newsPayload(['title' => 'Samodzielna']))->assertSessionHasNoErrors();
        $news = News::where('title', 'Samodzielna')->firstOrFail();
        $this->assertTrue($news->is_published);
        $this->assertFalse($news->pending_approval);
        Notification::assertNothingSent();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]))
            ->post(route('admin.newsy.store'), $this->newsPayload(['title' => 'Do akceptacji']))->assertSessionHasNoErrors();
        $pending = News::where('title', 'Do akceptacji')->firstOrFail();
        $this->assertFalse($pending->is_published);
        $this->assertTrue($pending->pending_approval);
        Notification::assertSentTimes(ContentSubmittedForApproval::class, 1);
    }

    public function test_zapis_cudzej_tresci_bez_publikacji_nie_zdejmuje_jej_z_kolejki(): void
    {
        $this->useClassicNewsForm();
        $author = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $news = News::create(['title' => 'Zgłoszona', 'slug' => 'zgloszona', 'content' => '<p>x</p>', 'published_at' => now(),
            'is_published' => false, 'pending_approval' => true, 'submitted_by_id' => $author->id]);

        $this->actingAs($this->independent())->put(route('admin.newsy.update', $news), $this->newsPayload(['title' => 'Zgłoszona (poprawka)', 'is_published' => '0']))
            ->assertSessionHasNoErrors();

        $news->refresh();
        $this->assertSame('Zgłoszona (poprawka)', $news->title);
        $this->assertTrue($news->pending_approval, 'zgłoszenie dalej czeka na osobę zatwierdzającą');
        $this->assertSame($author->id, $news->submitted_by_id);
    }

    public function test_edytor_blokowy_publikuje_strone_bez_akceptacji(): void
    {
        if (! SiteSetting::current()->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony w ustawieniach testowych.');
        }
        $page = Page::create(['title' => 'Nowa strona', 'slug' => 'nowa-strona', 'type' => 'standard', 'is_published' => false]);
        $user = $this->independent();

        $this->actingAs($user)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()->assertSee('"canApprove":true', false);
        $this->actingAs($user)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Nowa strona', 'blocks' => [], 'settings' => ['is_published' => true],
        ])->assertOk();

        $page->refresh();
        $this->assertTrue($page->is_published);
        $this->assertFalse($page->pending_approval);
    }

    public function test_wersje_robocza_strony_publikuje_sam_bez_wysylania_do_akceptacji(): void
    {
        if (! SiteSetting::current()->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony w ustawieniach testowych.');
        }
        $blocks = BlockRenderer::normalize([['type' => 'paragraph', 'attrs' => ['text' => 'Stara treść']]]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-robocza', 'type' => 'standard', 'is_published' => true]);
        $page->updateFromBlockEditor(['blocks' => $blocks, 'content' => BlockRenderer::render($blocks)]);
        $user = $this->independent();

        $this->actingAs($user)->putJson(route('admin.podstrony.bloki.update', $page).'?robocza=1', [
            'title' => 'Strona', 'meta_description' => null, 'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'Nowa treść']]],
        ])->assertOk();

        $html = $this->actingAs($user)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Opublikuj zmiany', $html);
        $this->assertStringNotContainsString('Wyślij do akceptacji', $html);

        $this->actingAs($user)->post(route('admin.podstrony.robocza.opublikuj', $page))->assertSessionHas('status');
        $this->assertStringContainsString('Nowa treść', $page->fresh()->content);
        $this->assertSame(0, PageDraft::count());
    }

    public function test_podglad_menu_odroznia_samodzielnego_edytora_od_edytora_tresci(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.uzytkownicy.podglad-menu'))->assertOk()
            ->assertSee('value="rola:independent_editor"', false);
        $this->actingAs($admin)->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:'.User::ROLE_INDEPENDENT_EDITOR]))->assertOk()
            ->assertSee('Menu: Samodzielny edytor')
            ->assertSee('od razu, bez akceptacji (nie zatwierdza zgłoszeń innych)');
        $this->actingAs($admin)->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:'.User::ROLE_CONTENT_EDITOR]))->assertOk()
            ->assertSee('po akceptacji osoby z prawem zatwierdzania');
    }

    public function test_macierz_uprawnien_pokazuje_sposob_publikacji(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true]);
        User::factory()->create(['role' => User::ROLE_INDEPENDENT_EDITOR, 'page_mounts' => [$page->id]]);

        $this->actingAs($admin)->get(route('admin.uzytkownicy.uprawnienia'))->assertOk()
            ->assertSee('publikuje bez akceptacji')
            ->assertSee('własne gałęzie stron');
    }
}
