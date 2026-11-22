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

    public function test_form_saves_image_alt(): void
    {
        $slide = HeroSlide::create(['site_id' => SiteSetting::current()->id, 'title' => 'Zdjęcie']);

        $this->actingAs($this->admin())->get(route('admin.hero.edit', $slide))->assertOk()
            ->assertSee('name="image_alt"', false)->assertSee('Opis zdjęcia (tekst alternatywny)');

        $this->actingAs($this->admin())->put(route('admin.hero.update', $slide), ['title' => 'Zdjęcie', 'image_alt' => 'Dzieci malują ozdoby'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Dzieci malują ozdoby', $slide->fresh()->image_alt);
    }

    /** @return array<string, array{string}> */
    public static function templates(): array
    {
        return ['domyślny' => ['default'], 'FEER' => ['feer'], 'NGO' => ['ngo'], 'VM' => ['vm'], 'BU2' => ['bu2'], 'gmina' => ['municipality']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('templates')]
    public function test_second_button_and_alt_on_home_slider(string $template): void
    {
        $site = SiteSetting::current();
        $site->forceFill(['site_template' => $template])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $slide = HeroSlide::create([
            'site_id' => $site->id, 'title' => 'Warsztaty zimowe', 'text' => 'Zapisy trwają.',
            'button_label' => 'Zapisz się', 'button_url' => '/kontakt',
            'button2_label' => 'Oferta warsztatów', 'button2_url' => '/oferta-zimowa',
            'image_alt' => 'Dzieci lepią bałwana z masy solnej',
        ]);
        $slide->addMedia(UploadedFile::fake()->image('slajd.jpg', 1200, 600))->toMediaCollection('image');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="/kontakt"', $html, $template.': pierwszy przycisk');
        $this->assertStringContainsString('href="/oferta-zimowa"', $html, $template.': drugi przycisk');
        $this->assertStringContainsString('Oferta warsztatów', $html);
        $this->assertStringContainsString('Dzieci lepią bałwana z masy solnej', $html, $template.': opis zdjęcia');
    }

    public function test_second_button_needs_link(): void
    {
        $slide = HeroSlide::create(['site_id' => SiteSetting::current()->id, 'title' => 'Bez linku']);

        $this->actingAs($this->admin())->put(route('admin.hero.update', $slide), ['title' => 'Bez linku', 'button2_label' => 'Oferta'])
            ->assertSessionHasErrors('button2_url');
    }
}
