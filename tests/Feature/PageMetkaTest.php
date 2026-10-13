<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Metryczka strony: włączanie dla serwisu / typu / strony, autor edytowalny, osoba publikująca
 * zapisywana przez system (zmienić ją może tylko konto wspólne — wtedy panel pokazuje okno wyboru).
 */
class PageMetkaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettings();
    }

    private function resetSettings(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + ['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'parent_id' => '', 'is_published' => '1'];
    }

    public function test_visibility_priority_page_over_type_over_site(): void
    {
        $page = Page::create(['title' => 'A', 'slug' => 'a', 'type' => 'standard', 'is_published' => true]);
        $this->assertFalse($page->showsMetka());

        SiteSetting::current()->update(['metka_mode' => 'types', 'metka_types' => ['standard']]);
        $this->resetSettings();
        $this->assertTrue($page->showsMetka());

        $page->layout_data = ['metka' => 'hide'];
        $this->assertFalse($page->showsMetka());

        SiteSetting::current()->update(['metka_mode' => 'off']);
        $this->resetSettings();
        $page->layout_data = ['metka' => 'show'];
        $this->assertTrue($page->showsMetka());
    }

    public function test_publisher_is_set_by_system_and_cannot_be_changed_by_regular_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'anna@example.org', 'name' => 'Anna Nowak']);
        $other = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'is_published' => false]);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), $this->payload([
            'metka_author' => 'Zespół komunikacji', 'published_by_id' => $other->id, 'layout_data' => ['metka' => 'show'],
        ]))->assertRedirect();

        $page->refresh();
        $this->assertSame('Zespół komunikacji', $page->metka_author);
        $this->assertSame($admin->id, $page->published_by_id);
        $this->assertNotNull($page->first_published_at);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('Metryczka')->assertSee('Anna Nowak')->assertDontSee('id="metka-dialog"', false);
    }

    public function test_shared_account_chooses_publisher_and_sees_dialog(): void
    {
        $shared = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'strona@feer.org.pl']);
        $person = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Jan Kowalski']);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'is_published' => false]);

        $this->actingAs($shared)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('id="metka-dialog"', false)->assertSee('Kto publikuje tę stronę?');

        $this->actingAs($shared)->put(route('admin.podstrony.update', $page), $this->payload([
            'published_by_id' => $person->id, 'layout_data' => ['metka' => 'show', 'metka_entity' => '0'],
        ]))->assertRedirect();

        $page->refresh();
        $this->assertSame($person->id, $page->published_by_id);
        $this->assertFalse((bool) $page->layout_data['metka_entity']);

        // Osoba wskazana — okno już się nie pojawia.
        $this->actingAs($shared)->get(route('admin.podstrony.edit', $page))->assertOk()->assertDontSee('id="metka-dialog"', false);
    }

    public function test_front_shows_metka_and_no_updated_line(): void
    {
        $author = User::factory()->create(['name' => 'Ewa Autorka']);
        $this->actingAs($author);
        $page = Page::create(['title' => 'Strona z metką', 'slug' => 'z-metka', 'type' => 'standard', 'is_published' => true,
            'created_by' => $author->id, 'metka_author' => 'Redakcja', 'layout_data' => ['metka' => 'show']]);
        auth()->logout();
        SiteSetting::current()->update(['metka_entity' => 'Fundacja Testowa']);
        $this->resetSettings();

        $this->get($page->publicUrl())->assertOk()
            ->assertSee('Pokaż metkę')->assertSee('Redakcja')->assertSee('Ewa Autorka')->assertSee('Fundacja Testowa')
            ->assertDontSee('Zaktualizowano');
    }

    public function test_settings_save_mode_and_types(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()->assertSee('Metryczka stron');

        $settings = SiteSetting::current();
        $settings->update(['metka_mode' => 'types', 'metka_types' => ['standard'], 'metka_shared_accounts' => "a@x.pl\nB@x.pl"]);
        $this->assertSame(['a@x.pl', 'b@x.pl'], $settings->metkaSharedEmails());
        $settings->update(['metka_shared_accounts' => null]);
        $this->assertContains('webmaster@ziemowitgil.pl', $settings->metkaSharedEmails());
    }

    public function test_metka_in_feer_template_also_on_contact_page(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'metka_mode' => 'all']);
        $this->resetSettings();
        foreach (['standard', 'about', 'contact'] as $type) {
            $page = Page::create(['title' => 'T '.$type, 'slug' => 'metka-'.$type, 'type' => $type, 'is_published' => true, 'content' => '<p>Treść</p>']);
            $this->get($page->publicUrl())->assertOk()->assertSee('Pokaż metkę');
        }
    }

    public function test_entity_can_be_hidden_for_whole_site(): void
    {
        SiteSetting::current()->update(['metka_entity' => 'Fundacja Testowa', 'metka_show_entity' => false]);
        $this->resetSettings();
        $page = Page::create(['title' => 'Bez podmiotu', 'slug' => 'bez-podmiotu', 'type' => 'standard', 'is_published' => true, 'layout_data' => ['metka' => 'show']]);

        $this->get($page->publicUrl())->assertOk()->assertSee('Pokaż metkę')
            ->assertDontSee('Podmiot publikujący')->assertDontSee('Fundacja Testowa');
    }
}
