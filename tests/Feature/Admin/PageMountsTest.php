<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Gałęzie stron przypisane osobie (users.page_mounts, jak „DB mounts” w TYPO3). */
class PageMountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function page(string $title, array $extra = []): Page
    {
        return Page::create($extra + ['title' => $title, 'slug' => str($title)->slug()->toString(), 'type' => 'standard', 'is_published' => true]);
    }

    private function editorWithMount(Page $mount): User
    {
        $u = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $u->forceFill(['page_mounts' => [$mount->id]])->save();

        return $u->fresh();
    }

    public function test_drzewo_zaczyna_sie_od_galezi_osoby(): void
    {
        $osrodek = $this->page('Ośrodek A');
        $this->page('Aktualności ośrodka', ['parent_id' => $osrodek->id]);
        $inny = $this->page('Ośrodek B');
        $editor = $this->editorWithMount($osrodek);

        $html = $this->actingAs($editor)->get(route('admin.podstrony.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Widzisz tylko swoje gałęzie stron: Ośrodek A', $html);
        $this->assertStringContainsString('data-label="Aktualności ośrodka"', $html);
        $this->assertStringNotContainsString('data-label="Ośrodek B"', $html);
        $this->assertStringNotContainsString('— poziom główny —', $html);

        $this->actingAs($editor)->get(route('admin.podstrony.edit', $inny))->assertNotFound();
    }

    public function test_nie_mozna_wyniesc_strony_poza_galaz(): void
    {
        $osrodek = $this->page('Ośrodek A');
        $child = $this->page('Podstrona', ['parent_id' => $osrodek->id]);
        $sub = $this->page('Druga', ['parent_id' => $osrodek->id]);
        $editor = $this->editorWithMount($osrodek);

        $this->actingAs($editor)->postJson(route('admin.podstrony.uloz'), ['id' => $child->id, 'parent_id' => null, 'position' => 0])
            ->assertForbidden();
        $this->actingAs($editor)->patch(route('admin.podstrony.przenies', $child), ['parent_id' => null])->assertSessionHas('error');
        $this->assertSame($osrodek->id, $child->fresh()->parent_id);

        // Wewnątrz gałęzi wolno
        $this->actingAs($editor)->postJson(route('admin.podstrony.uloz'), ['id' => $child->id, 'parent_id' => $sub->id, 'position' => 0])->assertOk();
        $this->assertSame($sub->id, $child->fresh()->parent_id);
    }

    public function test_administrator_ustawia_galezie_w_edycji_uzytkownika(): void
    {
        $osrodek = $this->page('Ośrodek A');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($admin)->get(route('admin.uzytkownicy.edit', $editor))->assertOk()
            ->assertSee('Gałęzie stron tej osoby')->assertSee('name="page_mounts[]" value="'.$osrodek->id.'"', false);

        $this->actingAs($admin)->put(route('admin.uzytkownicy.update', $editor), [
            'name' => $editor->name, 'email' => $editor->email, 'role' => User::ROLE_CONTENT_EDITOR, 'page_mounts' => [$osrodek->id],
        ])->assertRedirect();
        $this->assertSame([$osrodek->id], $editor->fresh()->page_mounts);

        // Zmiana roli na administratora czyści gałęzie
        $this->actingAs($admin)->put(route('admin.uzytkownicy.update', $editor), [
            'name' => $editor->name, 'email' => $editor->email, 'role' => User::ROLE_ADMIN, 'page_mounts' => [$osrodek->id],
        ])->assertRedirect();
        $this->assertNull($editor->fresh()->page_mounts);
    }
}
