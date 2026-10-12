<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pola bez znaczenia w danym układzie strony są ukryte (data-hide-for-templates). */
class PageFormTemplateFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function form(string $template): string
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-'.$template, 'type' => 'standard', 'is_published' => true, 'page_template' => $template]);

        return $this->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();
    }

    public function test_uklad_szeroki_ukrywa_nawigacje_po_podstronach(): void
    {
        $html = $this->form('wide');
        $this->assertStringContainsString('data-hide-for-templates="wide hero landing minimal" class="hidden"', $html);
        $this->assertStringContainsString('data-hide-for-templates="section" class=""', $html);
    }

    public function test_strona_dzialu_ukrywa_zdjecie_w_tresci_i_galerie(): void
    {
        $html = $this->form('section');
        $this->assertSame(2, substr_count($html, 'data-hide-for-templates="section" class="hidden"'));
        $this->assertStringContainsString('data-hide-for-templates="wide hero landing minimal" class=""', $html);
    }

    public function test_uklad_domyslny_pokazuje_wszystko(): void
    {
        $this->assertStringNotContainsString('data-hide-for-templates="section" class="hidden"', $this->form('default'));
    }
}
