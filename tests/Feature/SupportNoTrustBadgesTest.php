<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Strony wsparcia i darowizny bez KRS i „sygnałów zaufania”; w nagłówku wsparcia zdjęcie zastępcze, gdy brak własnego. */
class SupportNoTrustBadgesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function template(string $template): void
    {
        SiteSetting::current()->forceFill(['site_template' => $template, 'krs_number' => '0000779281'])->save();
        $this->reset();
    }

    public function test_wsparcie_i_darowizna_bez_krs_i_sygnalow_zaufania(): void
    {
        foreach (['feer', 'default'] as $template) {
            $this->template($template);
            foreach ([route('support.show'), route('donation.show')] as $url) {
                $this->get($url)->assertOk()
                    ->assertDontSee('0000779281')
                    ->assertDontSee('Możesz wpłacić anonimowo')
                    ->assertDontSee('Bezpieczna płatność')
                    ->assertDontSee('Dlaczego możesz nam zaufać');
            }
        }
    }

    public function test_bez_wlasnego_zdjecia_naglowek_wsparcia_bierze_zdjecie_zastepcze(): void
    {
        $this->template('feer');
        $this->assertNull(SiteSetting::current()->supportHeroPhotoUrl());

        $slide = HeroSlide::create(['title' => 'Slajd', 'order' => 1]);
        $slide->addMedia(UploadedFile::fake()->image('warsztaty.jpg', 800, 600))->toMediaCollection('image');
        $this->reset();

        $url = SiteSetting::current()->supportHeroPhotoUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('warsztaty', $url);
        $this->get(route('support.show'))->assertOk()
            ->assertSee('src="'.$url.'"', false)
            ->assertDontSee('Miejsce na zdjęcie');
    }
}
