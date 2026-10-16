<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Domyślny edytor (Mój profil): wybór w profilu, pytanie po zalogowaniu i otwieranie „Edytuj” w wybranym edytorze. */
class DefaultEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(?string $editor = null): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'default_editor' => $editor]);
    }

    private function page(array $attributes = []): Page
    {
        return Page::create($attributes + ['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'content' => '<p>Treść</p>', 'is_published' => true]);
    }

    public function test_profile_shows_default_editor_setting(): void
    {
        $this->actingAs($this->admin())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Domyślny edytor')
            ->assertSee('Zapisz domyślny edytor')
            ->assertDontSee('id="default-editor-prompt"', false);
    }

    public function test_default_editor_can_be_saved_in_profile(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->patch(route('profile.editor'), ['default_editor' => User::EDITOR_BLOCKS])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit').'#domyslny-edytor');

        $this->assertSame(User::EDITOR_BLOCKS, $user->refresh()->default_editor);
    }

    public function test_invalid_editor_is_rejected(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->patch(route('profile.editor'), ['default_editor' => 'word'])
            ->assertSessionHasErrors('default_editor');

        $this->assertNull($user->refresh()->default_editor);
    }

    public function test_admin_panel_asks_for_editor_until_chosen(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->assertSee('id="default-editor-prompt"', false);

        $this->actingAs($user)->from(route('admin.dashboard'))
            ->patch(route('profile.editor'), ['default_editor' => User::EDITOR_CLASSIC, 'from_prompt' => 1])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($user->refresh())->get(route('admin.dashboard'))->assertOk()->assertDontSee('id="default-editor-prompt"', false);
    }

    public function test_later_hides_prompt_for_the_session_only(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->from(route('admin.dashboard'))->post(route('profile.editor.dismiss'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('default_editor_prompt_dismissed', true);

        $this->get(route('admin.dashboard'))->assertDontSee('id="default-editor-prompt"', false);
        $this->assertNull($user->refresh()->default_editor);
    }

    public function test_no_prompt_when_block_editor_is_disabled(): void
    {
        SiteSetting::current()->update(['disabled_modules' => ['block_editor']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertDontSee('id="default-editor-prompt"', false);
    }

    public function test_block_preference_opens_block_editor(): void
    {
        $page = $this->page();
        $news = News::create(['title' => 'Aktualność', 'slug' => 'aktualnosc', 'content' => '<p>x</p>', 'is_published' => true]);
        $admin = $this->admin(User::EDITOR_BLOCKS);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertRedirect(route('admin.podstrony.bloki.edit', $page));
        $this->actingAs($admin)->get(route('admin.newsy.edit', $news))->assertRedirect(route('admin.edytor-blokowy.edit', ['news', $news->id]));

        // Z edytora blokowego da się wrócić do formularza.
        $this->actingAs($admin)->get(route('admin.podstrony.edit', [$page, 'klasyczny' => 1]))->assertOk()->assertSee('Otwórz w edytorze blokowym');
        $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()
            ->assertSee(str_replace('/', '\/', route('admin.podstrony.edit', $page).'?klasyczny=1'), false);
    }

    public function test_block_preference_keeps_form_for_pages_without_free_content(): void
    {
        $contact = $this->page(['slug' => 'kontakt', 'type' => 'contact']);

        $this->actingAs($this->admin(User::EDITOR_BLOCKS))->get(route('admin.podstrony.edit', $contact))->assertOk();
    }

    public function test_classic_preference_opens_form(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin(User::EDITOR_CLASSIC))->get(route('admin.podstrony.edit', $page))->assertOk();
    }
}
