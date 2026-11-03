<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Domyślny edytor (Mój profil): wybór w profilu, domyślnie blokowy (bez pytania po zalogowaniu) i otwieranie „Edytuj” w wybranym edytorze. */
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

    public function test_block_editor_is_the_default_and_no_prompt_is_shown(): void
    {
        $user = $this->admin();

        $this->assertSame(User::EDITOR_BLOCKS, $user->effectiveEditor());
        $this->assertTrue($user->prefersBlockEditor());
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->assertDontSee('id="default-editor-prompt"', false);
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('value="blocks" checked', false);
    }

    public function test_default_opens_block_editor_without_a_saved_choice(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertRedirect(route('admin.podstrony.bloki.edit', $page));
    }

    public function test_classic_choice_is_respected_and_unknown_value_falls_back_to_blocks(): void
    {
        $this->assertFalse($this->admin(User::EDITOR_CLASSIC)->prefersBlockEditor());
        $this->assertTrue($this->admin('inny')->prefersBlockEditor());
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
