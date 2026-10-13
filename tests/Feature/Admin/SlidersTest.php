<?php

namespace Tests\Feature\Admin;

use App\Models\HeroSlide;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlidersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_create_slider_add_slides_reorder_and_assign_placements(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.slidery.store'), ['name' => 'Strona główna'])->assertRedirect();
        $home = Slider::firstOrFail();
        $this->assertTrue($home->is_home, 'Pierwszy slider trafia na stronę główną.');

        $this->actingAs($admin)->post(route('admin.slidery.store'), ['name' => 'Blog'])->assertRedirect();
        $blog = Slider::where('name', 'Blog')->firstOrFail();
        $this->assertFalse($blog->is_home);

        foreach (['Pierwszy', 'Drugi'] as $title) {
            $this->actingAs($admin)->post(route('admin.hero.store'), ['title' => $title, 'slider_id' => $blog->id, 'image' => UploadedFile::fake()->image('a.jpg')])
                ->assertRedirect(route('admin.slidery.show', $blog));
        }
        $this->assertSame(2, $blog->slides()->count());
        $this->assertSame(0, $home->slides()->count());

        $second = HeroSlide::where('title', 'Drugi')->first();
        $this->actingAs($admin)->post(route('admin.slidery.move', [$blog, $second]), ['kierunek' => 'gora'])->assertRedirect();
        $this->assertSame(['Drugi', 'Pierwszy'], $blog->slides()->pluck('title')->all());

        // Blog dostaje ten slider; zaznaczenie strony głównej zdejmuje ją z poprzedniego.
        $this->actingAs($admin)->put(route('admin.slidery.update', $blog), ['name' => 'Blog', 'show_on_blog' => 1, 'is_home' => 1])->assertRedirect();
        $this->assertTrue($blog->fresh()->show_on_blog);
        $this->assertTrue($blog->fresh()->is_home);
        $this->assertFalse($home->fresh()->is_home);

        $this->actingAs($admin)->get(route('admin.hero.index'))->assertOk()->assertSee('Slidery')->assertSee('Blog')->assertSee('Strona główna, Blog');
        $this->actingAs($admin)->get(route('admin.slidery.show', $blog))->assertOk()->assertSee('Dodaj slajd')->assertSee('Drugi');
    }

    public function test_page_shows_selected_slider_above_content(): void
    {
        $slider = Slider::create(['name' => 'Szkolenia']);
        HeroSlide::create(['slider_id' => $slider->id, 'title' => 'Zapisy na jesień ruszyły', 'order' => 0]);
        $page = Page::create(['title' => 'Szkolenia', 'slug' => 'szkolenia-x', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Treść</p>',
            'layout_data' => ['hero_slider_id' => $slider->id]]);

        $this->get($page->publicUrl())->assertOk()->assertSee('Zapisy na jesień ruszyły')->assertSee('Slider: Szkolenia');

        $this->actingAs($this->admin())->get(route('admin.slidery.show', $slider))->assertSee('Szkolenia');
    }
}
