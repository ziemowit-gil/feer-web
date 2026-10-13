<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuLinkPageTypesTest extends TestCase
{
    use RefreshDatabase;

    private function section(): Page
    {
        $root = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true, 'show_side_nav' => true]);
        Page::create(['title' => 'Zwykła', 'slug' => 'zwykla', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id, 'show_side_nav' => true]);
        Page::create(['title' => 'Facebook', 'slug' => 'nasz-facebook', 'type' => 'menu_link', 'is_published' => true, 'parent_id' => $root->id,
            'type_data' => ['target_url' => 'https://facebook.com/feer', 'new_tab' => true]]);
        Page::create(['title' => 'Stary adres', 'slug' => 'stary-adres', 'type' => 'redirect', 'is_published' => true, 'parent_id' => $root->id,
            'type_data' => ['target_url' => '/nowy-adres']]);
        $heading = Page::create(['title' => 'Do pobrania', 'slug' => 'do-pobrania', 'type' => 'menu_heading', 'is_published' => true, 'parent_id' => $root->id]);
        Page::create(['title' => 'Statut', 'slug' => 'statut', 'type' => 'standard', 'is_published' => true, 'parent_id' => $heading->id]);

        return $root;
    }

    public function test_menu_boczne_linkuje_wprost_i_pokazuje_naglowek_bez_linku(): void
    {
        $this->section();

        $html = $this->get('/zwykla')->assertOk()->getContent();

        $this->assertStringContainsString('href="https://facebook.com/feer" target="_blank" rel="noopener"', $html);
        $this->assertStringContainsString('(otwiera się w nowej karcie)', $html);
        $this->assertStringContainsString('href="/nowy-adres"', $html);
        $this->assertMatchesRegularExpression('~<p[^>]*>\s*Do pobrania\s*</p>~', $html);
        $this->assertStringNotContainsString('href="'.url('/do-pobrania').'"', $html);
        $this->assertStringContainsString('Statut', $html);
    }

    public function test_adresy_linku_i_naglowka_przekierowuja(): void
    {
        $this->section();

        $this->get(Page::where('slug', 'nasz-facebook')->first()->publicUrl())->assertRedirect('https://facebook.com/feer');
        $this->get(Page::where('slug', 'do-pobrania')->first()->publicUrl())->assertRedirect(Page::where('slug', 'statut')->first()->publicUrl());
    }

    public function test_przekierowanie_w_trybie_zakladki_dzialania_jest_linkiem(): void
    {
        $category = Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        $project = Project::create(['title' => 'Edukacja', 'slug' => 'edukacja', 'category_id' => $category->id, 'content' => '<p>Opis</p>', 'is_published' => true, 'sections_nav' => 'sidebar']);
        Page::create(['title' => 'Zakres', 'slug' => 'zakres', 'type' => 'standard', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab', 'content' => '<p>Zakres</p>']);
        Page::create(['title' => 'Profil na Facebooku', 'slug' => 'profil-fb', 'type' => 'menu_link', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab',
            'type_data' => ['target_url' => 'https://facebook.com/feer', 'new_tab' => true]]);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        $this->assertStringContainsString('href="https://facebook.com/feer" target="_blank" rel="noopener"', $html);
        $this->assertStringNotContainsString("tab = 'podstrona-1'", $html, 'link nie jest zakładką');
        $this->assertStringContainsString("tab = 'podstrona-0'", $html);
    }

    public function test_formularz_linku_pokazuje_tylko_niezbedne_pola(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $link = Page::create(['title' => 'Profil', 'slug' => 'profil', 'type' => 'menu_link', 'is_published' => true, 'type_data' => ['target_url' => 'https://facebook.com/feer']]);
        $standard = Page::create(['title' => 'Zwykła', 'slug' => 'zwykla-2', 'type' => 'standard', 'is_published' => true]);

        $linkHtml = $this->get(route('admin.podstrony.edit', $link))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~data-content-field class="hidden" data-hide-for-link~', $linkHtml);
        $this->assertMatchesRegularExpression('~data-pe-section="seo" data-hide-for-link class="hidden"~', $linkHtml);
        $this->assertStringContainsString('name="type_data[target_url]"', $linkHtml);
        $this->assertStringContainsString('name="title"', $linkHtml);

        $standardHtml = $this->get(route('admin.podstrony.edit', $standard))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~data-content-field class="" data-hide-for-link~', $standardHtml);
    }

    public function test_drzewo_dzialan_w_panelu_odroznia_link_od_zakladki(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $category = Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        $project = Project::create(['title' => 'Edukacja', 'slug' => 'edukacja', 'category_id' => $category->id, 'content' => '<p>Opis</p>', 'is_published' => true]);
        Page::create(['title' => 'Zakres', 'slug' => 'zakres', 'type' => 'standard', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab']);
        Page::create(['title' => 'Profil FB', 'slug' => 'profil-fb', 'type' => 'menu_link', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab',
            'type_data' => ['target_url' => 'https://facebook.com/feer']]);

        $this->assertSame('Zakładka', Page::where('slug', 'zakres')->first()->projectDisplayInfo()['label']);
        $this->assertSame(['label' => 'Link', 'link' => true, 'target' => 'https://facebook.com/feer'], Page::where('slug', 'profil-fb')->first()->projectDisplayInfo());

        $this->get(route('admin.projekty.index', ['wybrana' => $project->id]))->assertOk()
            ->assertSee('Zakładka')
            ->assertSee('→ https://facebook.com/feer')
            ->assertSee('Dodaj link');
    }
}
