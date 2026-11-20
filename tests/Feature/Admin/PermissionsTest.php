<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Uprawnienia jak w TYPO3: sumowanie grup, typy bloków i pola zablokowane dla grupy, przełączanie na konto
 * redaktora, ważność konta od–do z wygaszaniem przez cron i dziennik zdarzeń ze starymi/nowymi wartościami.
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function editor(array $groups, array $attrs = []): User
    {
        $primary = array_shift($groups);
        $user = User::factory()->create($attrs + ['role' => User::ROLE_EDITOR, 'user_group_id' => $primary?->id]);
        $user->extraGroups()->sync(array_map(fn ($g) => $g->id, $groups));

        return $user->fresh();
    }

    // ── 1. Sumowanie uprawnień z grup ──────────────────────────────────────

    public function test_permissions_from_several_groups_are_added_up(): void
    {
        $dept = Page::create(['title' => 'Szkolenia', 'slug' => 'szkolenia', 'is_published' => true]);
        $child = Page::create(['title' => 'Kurs', 'slug' => 'kurs', 'is_published' => true, 'parent_id' => $dept->id]);
        $other = Page::create(['title' => 'Inne', 'slug' => 'inne', 'is_published' => true]);

        $news = UserGroup::create(['name' => 'Redakcja aktualności', 'modules' => ['news'], 'own_content_only' => true]);
        $training = UserGroup::create(['name' => 'Koordynacja szkoleń', 'modules' => ['pages'], 'page_ids' => [$dept->id], 'can_approve' => true, 'block_types' => ['paragraph']]);
        $user = $this->editor([$news, $training]);

        $this->assertTrue($user->canAccessModule('news'));
        $this->assertTrue($user->canAccessModule('pages'));
        $this->assertFalse($user->canAccessModule('bip'));
        $this->assertTrue($user->canApproveContent(), 'publikować może, gdy pozwala na to którakolwiek grupa');
        $this->assertFalse($user->permissionGroup()->own_content_only, '„tylko własne” tylko, gdy mają to wszystkie grupy');
        $this->assertEqualsCanonicalizing([$dept->id, $child->id], $user->allowedPageIds());
        $this->assertNotContains($other->id, $user->allowedPageIds());
        $this->assertNull($user->allowedBlockTypes(), 'grupa bez listy bloków znosi ograniczenie');
        $this->assertTrue(User::approvers()->whereKey($user->id)->exists());
    }

    // ── 3. Typy bloków i pola zablokowane ──────────────────────────────────

    public function test_group_cannot_add_disallowed_blocks_but_keeps_existing_ones(): void
    {
        if (! SiteSetting::current()->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony.');
        }
        $blocks = BlockRenderer::normalize([['type' => 'html', 'attrs' => ['html' => '<p>Od administratora</p>']]]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-bloki', 'is_published' => true, 'blocks' => $blocks, 'content' => BlockRenderer::render($blocks)]);
        $intern = UserGroup::create(['name' => 'Stażyści', 'modules' => ['pages'], 'block_types' => ['paragraph', 'heading']]);
        $user = $this->editor([$intern]);

        $this->actingAs($user)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()
            ->assertSee('"permissions":{"blocks":["paragraph","heading"]', false);

        $this->actingAs($user)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Strona', 'meta_description' => null,
            'blocks' => [
                ['type' => 'paragraph', 'attrs' => ['text' => 'Nowy akapit']],
                ['type' => 'html', 'attrs' => ['html' => '<p>Od administratora</p>']],
                ['type' => 'html', 'attrs' => ['html' => '<script>alert(1)</script>']],
                ['type' => 'details', 'attrs' => ['summary' => 'Sekcja'], 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'W środku']]]],
            ],
        ])->assertOk();

        $saved = $page->fresh()->blocks;
        $this->assertSame(['paragraph', 'html', 'paragraph'], array_column($saved, 'type'));
        $this->assertSame('<p>Od administratora</p>', $saved[1]['attrs']['html']);
        $this->assertSame('W środku', $saved[2]['attrs']['text'], 'niedozwolony kontener zostawia treść');
    }

    public function test_locked_fields_keep_their_values_whatever_form_saves_them(): void
    {
        $page = Page::create(['title' => 'Stary', 'slug' => 'stary-adres', 'meta_title' => 'SEO', 'is_published' => true]);
        $group = UserGroup::create(['name' => 'Wolontariusze', 'modules' => ['pages'], 'locked_fields' => ['slug', 'seo']]);
        $user = $this->editor([$group]);

        $this->actingAs($user);
        $page->update(['title' => 'Nowy', 'slug' => 'nowy-adres', 'meta_title' => 'Inny SEO']);
        $page->refresh();
        $this->assertSame('Nowy', $page->title);
        $this->assertSame('stary-adres', $page->slug);
        $this->assertSame('SEO', $page->meta_title);

        // Pole jest zablokowane tylko, gdy blokują je wszystkie grupy osoby.
        $free = UserGroup::create(['name' => 'Redakcja', 'modules' => ['pages'], 'locked_fields' => ['seo']]);
        $both = $this->editor([$group, $free]);
        $this->assertSame(['seo'], $both->lockedFields());
    }

    // ── 4. Przełączanie na konto redaktora ─────────────────────────────────

    public function test_admin_switches_to_an_editor_and_back_with_audit_entries(): void
    {
        $admin = $this->admin();
        $group = UserGroup::create(['name' => 'Redakcja', 'modules' => ['news']]);
        $editor = $this->editor([$group], ['name' => 'Anna Redaktorka']);

        $this->actingAs($admin)->post(route('admin.uzytkownicy.przelacz', $editor))->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($editor);
        $this->assertSame($admin->id, session('impersonator_id'));

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Pracujesz jako', false)->assertSee('Wróć do swojego konta', false);

        // Hasła ani zabezpieczeń tej osoby nie da się zmienić.
        $this->from(route('profile.edit'))->put(route('password.update'), ['current_password' => 'x', 'password' => 'Nowe-haslo-123', 'password_confirmation' => 'Nowe-haslo-123'])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('error');

        $this->post(route('admin.przelacz.wroc'))->assertRedirect(route('admin.uzytkownicy.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));

        $started = Activity::where('event', 'impersonation_started')->first();
        $this->assertNotNull($started);
        $this->assertSame($admin->id, (int) $started->causer_id);
        $this->assertSame($editor->id, (int) $started->subject_id);
        $this->assertNotEmpty($started->properties->get('ip'));
        $this->assertTrue(Activity::where('event', 'impersonation_stopped')->exists());
        $this->assertFalse(Activity::where('event', 'login')->where('causer_id', $editor->id)->exists(), 'przełączenie nie udaje logowania');
    }

    public function test_cannot_switch_to_another_admin(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAs($admin)->post(route('admin.uzytkownicy.przelacz', $other))->assertSessionHas('error');
        $this->assertAuthenticatedAs($admin);
    }

    // ── 5. Ważność konta ───────────────────────────────────────────────────

    public function test_expired_account_cannot_log_in_and_open_session_is_closed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR, 'password' => Hash::make('Haslo-123456'), 'active_until' => now()->subDay()]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Haslo-123456'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(Activity::where('event', 'login_refused')->exists());

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        $pending = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR, 'active_from' => now()->addWeek()]);
        $this->assertSame('pending', $pending->accountWindowState());
    }

    public function test_cron_expires_accounts_once(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR, 'active_until' => now()->subHour(), 'remember_token' => 'abc']);

        $this->artisan('users:wygas')->assertSuccessful();
        $user->refresh();
        $this->assertNotNull($user->expiry_handled_at);
        $this->assertNull($user->remember_token);
        $this->artisan('users:wygas')->assertSuccessful();
        $this->assertSame(1, Activity::where('event', 'account_expired')->count());
    }

    public function test_admin_sets_extra_groups_and_account_window_in_user_form(): void
    {
        $admin = $this->admin();
        $a = UserGroup::create(['name' => 'A', 'modules' => ['news']]);
        $b = UserGroup::create(['name' => 'B', 'modules' => ['pages']]);
        $user = $this->editor([$a]);

        $this->actingAs($admin)->put(route('admin.uzytkownicy.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => User::ROLE_EDITOR, 'user_group_id' => $a->id,
            'extra_group_ids' => [$b->id, $a->id], 'active_from' => '', 'active_until' => '2030-06-30T16:00',
        ])->assertRedirect(route('admin.uzytkownicy.index'));

        $user->refresh();
        $this->assertSame([$b->id], $user->extraGroups()->pluck('user_groups.id')->all(), 'grupa główna nie dubluje się jako dodatkowa');
        $this->assertSame('2030-06-30 14:00', $user->active_until->format('Y-m-d H:i'), 'czas polski zapisany w UTC');
        $changed = Activity::where('event', 'groups_changed')->latest('id')->first();
        $this->assertSame('B', $changed->properties->get('attributes')['dodatkowe_grupy']);
    }

    // ── 6. Dziennik zdarzeń ────────────────────────────────────────────────

    public function test_audit_log_stores_old_and_new_values_and_logins(): void
    {
        $admin = $this->admin();
        $page = Page::create(['title' => 'Przed', 'slug' => 'audyt', 'is_published' => true]);

        $this->actingAs($admin);
        $page->update(['title' => 'Po', 'content' => '<p>Długa treść</p>']);
        $entry = Activity::where('event', 'updated')->where('subject_id', $page->id)->latest('id')->first();
        $changes = collect($entry->changes())->keyBy('field');
        $this->assertSame(['old' => 'Przed', 'new' => 'Po'], ['old' => $changes['title']['old'], 'new' => $changes['title']['new']]);
        $this->assertSame('[treść]', $changes['content']['new']);

        auth()->logout();
        $user = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR, 'password' => Hash::make('Haslo-123456')]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'zle'])->assertSessionHasErrors();
        $this->assertTrue(Activity::where('log_name', 'auth')->where('event', 'login_failed')->exists());

        $this->actingAs($admin)->get(route('admin.dziennik.index', ['log' => 'auth']))->assertOk()->assertSee('Nieudane logowanie', false);
        $this->actingAs($admin)->get(route('admin.dziennik.index'))->assertOk()->assertSee('Zmienione pola', false);
    }
}
