<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Formularz pozycji menu: wybór strony z drzewa stron i podstron w oknie (zamiast płaskiej listy). */
class NavPagePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_okno_z_drzewem_stron_i_podstron(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $parent = Page::create(['title' => 'Fundacja Picker', 'slug' => 'fundacja-picker', 'content' => '<p>x</p>', 'is_published' => true]);
        Page::create(['title' => 'Sprawozdania Picker', 'slug' => 'sprawozdania-picker', 'content' => '<p>x</p>', 'is_published' => true, 'parent_id' => $parent->id]);
        Page::create(['title' => 'Szkic Picker', 'slug' => 'szkic-picker', 'content' => '<p>x</p>', 'is_published' => false]);

        foreach ([route('admin.pozycje-menu.index'), route('admin.pozycje-menu.create')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()
                ->assertSee('Wybierz z drzewa stron')
                ->assertSee('aria-labelledby="nav-page-picker-title"', false)
                ->assertDontSee('— wybierz z listy stron —')
                ->getContent();

            // Dane drzewa: podstrona ma rodzica w „parents” i poziom 1, szkic jest oznaczony.
            $this->assertMatchesRegularExpression('/Sprawozdania Picker.{0,80}sprawozdania-picker.{0,40}depth.{0,10}1.{0,30}parents.{0,10}\['.$parent->id.'\]/s', html_entity_decode(stripslashes($html)));
            $this->assertMatchesRegularExpression('/Szkic Picker.{0,200}draft.{0,10}true/s', html_entity_decode(stripslashes($html)));
        }
    }
}
