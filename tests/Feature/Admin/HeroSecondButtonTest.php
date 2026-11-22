<?php

namespace Tests\Feature\Admin;

use App\Models\HeroSlide;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Drugi przycisk slajdu (button2_label / button2_url): formularz, zapis, walidacja. */
class HeroSecondButtonTest extends TestCase
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

    public function test_form_shows_and_saves_second_button(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.hero.create'))->assertOk()
            ->assertSee('name="button2_label"', false)
            ->assertSee('name="button2_url"', false)
            ->assertSee('Etykieta drugiego przycisku');

        $this->actingAs($admin)->post(route('admin.hero.store'), [
            'title' => 'Warsztaty', 'button_label' => 'Relacje', 'button_url' => 'https://facebook.com/x',
            'button2_label' => 'Oferta warsztatów', 'button2_url' => '#oferta',
            'image' => UploadedFile::fake()->image('a.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $slide = HeroSlide::query()->where('title', 'Warsztaty')->firstOrFail();
        $this->assertSame('Oferta warsztatów', $slide->button2_label);
        $this->assertSame('#oferta', $slide->button2_url);

        $this->actingAs($admin)->put(route('admin.hero.update', $slide), ['title' => 'Warsztaty', 'button2_label' => '', 'button2_url' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($slide->fresh()->button2_label);
    }

    public function test_second_button_needs_link(): void
    {
        $slide = HeroSlide::create(['site_id' => SiteSetting::current()->id, 'title' => 'Bez linku']);

        $this->actingAs($this->admin())->put(route('admin.hero.update', $slide), ['title' => 'Bez linku', 'button2_label' => 'Oferta'])
            ->assertSessionHasErrors('button2_url');
    }
}
