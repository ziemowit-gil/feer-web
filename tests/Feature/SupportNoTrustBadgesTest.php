<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\News;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

    public function test_dzialania_ktore_wspierasz_jako_kafle_z_ikona_w_tle(): void
    {
        $this->template('feer');
        foreach (['Szkolenia i warsztaty', 'Edukacja i materiały', 'Wsparcie dla organizacji'] as $i => $name) {
            $category = Category::create(['name' => $name, 'slug' => 'kat-'.$i]);
            Project::create(['category_id' => $category->id, 'title' => 'Działanie '.$i, 'slug' => 'dzialanie-'.$i, 'is_published' => true]);
        }

        $html = $this->get(route('support.show'))->assertOk()->getContent();
        $section = substr($html, strpos($html, 'id="programs-heading"'), 12000);
        foreach (['Szkolenia i warsztaty', 'Działanie 0', 'Wsparcie dla organizacji', 'Zobacz wszystkie w tym obszarze'] as $text) {
            $this->assertStringContainsString($text, $section);
        }
        $this->assertStringContainsString('>01<', $section);
        $this->assertStringContainsString('bg-brand-light text-ink', $section);
        $this->assertStringContainsString('bg-ink text-white', $section);
        $this->assertStringContainsString('-z-10 select-none text-[8.5rem]', $section, 'ikona kategorii w tle');
    }

    public function test_aktualnosci_na_stronie_wsparcia_jak_na_stronie_glownej(): void
    {
        $this->template('feer');
        Cache::flush();
        News::create(['title' => 'Zmiana rachunku bankowego', 'slug' => 'zmiana-rachunku', 'content' => '<p>x</p>', 'excerpt' => 'Nowy numer konta.', 'is_published' => true, 'published_at' => now()->subDay()]);

        $html = $this->get(route('support.show'))->assertOk()->getContent();
        $section = substr($html, strpos($html, '<section class="py-14" aria-labelledby="latest-news-heading">'), 6000);
        $this->assertStringContainsString('Co ostatnio u nas słychać', $section);
        $this->assertStringContainsString('<a href="'.route('news.show', 'zmiana-rachunku').'" class="stretched-link', $section);
        $this->assertStringContainsString('Nowy numer konta.', $section);
        $this->assertStringContainsString('<time datetime="'.now()->subDay()->toDateString().'"', $section);
        $this->assertStringContainsString('sm:grid-cols-2 lg:grid-cols-3', $section);
    }
}
