<?php

namespace Tests\Feature\Admin;

use App\Models\HeroSlide;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroUnsplashTest extends TestCase
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

    public function test_formularz_slajdu_pokazuje_wybieracz_unsplash_gdy_jest_klucz(): void
    {
        SiteSetting::current()->update(['unsplash_access_key' => 'test-key']);

        $this->actingAs($this->admin())->get(route('admin.hero.create'))->assertOk()
            ->assertSee('Albo wybierz zdjęcie z Unsplash')
            ->assertSee('name="unsplash_full_url"', false);
    }

    public function test_formularz_slajdu_bez_klucza_nie_pokazuje_wybieracza(): void
    {
        SiteSetting::current()->update(['unsplash_access_key' => null]);
        config(['services.unsplash.access_key' => null]);

        $this->actingAs($this->admin())->get(route('admin.hero.create'))->assertOk()
            ->assertDontSee('Albo wybierz zdjęcie z Unsplash');
    }

    public function test_slajd_wymaga_pliku_albo_zdjecia_z_unsplash(): void
    {
        $this->actingAs($this->admin())->post(route('admin.hero.store'), ['title' => 'Nowy'])
            ->assertSessionHasErrors('image');
    }

    public function test_slajd_z_plikiem_dalej_sie_zapisuje(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.hero.store'), [
            'title' => 'Nowy', 'image' => UploadedFile::fake()->image('a.jpg', 800, 600),
        ])->assertRedirect(route('admin.hero.index'));

        $this->assertSame(1, HeroSlide::count());
    }
}
