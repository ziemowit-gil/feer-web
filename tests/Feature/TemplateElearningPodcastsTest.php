<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Podcast;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Szablony „elearning” (platforma kursów) i „podcasts” (odcinki): strona główna, nagłówek, brak stylów w innych szablonach. */
class TemplateElearningPodcastsTest extends TestCase
{
    use RefreshDatabase;

    private function useTemplate(string $template, array $extra = []): void
    {
        $reset = fn () => \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $reset();
        SiteSetting::current()->forceFill(['site_template' => $template] + $extra)->save();
        $reset();
    }

    public function test_szablony_sa_w_ustawieniach(): void
    {
        $this->assertArrayHasKey('elearning', SiteSetting::SITE_TEMPLATES);
        $this->assertArrayHasKey('podcasts', SiteSetting::SITE_TEMPLATES);
    }

    public function test_elearning_pokazuje_kursy_w_kategoriach_i_liczby(): void
    {
        $this->useTemplate('elearning', ['tagline' => 'Kursy online dla organizacji']);
        Course::create(['title' => 'Copilot od podstaw', 'slug' => 'copilot', 'category' => 'm365', 'format' => 'online', 'status' => 'active', 'audience' => [['text' => 'Liderzy i liderki']]]);
        Course::create(['title' => 'Szkic kursu', 'slug' => 'szkic', 'category' => 'wcag', 'format' => 'online', 'status' => 'draft']);

        $this->get('/')->assertOk()
            ->assertSee('class="el el-header"', false)->assertSee('template-elearning', false)
            ->assertSee('Kursy online dla organizacji')->assertSee('Microsoft 365')->assertSee('Copilot od podstaw')
            ->assertSee('kursów na platformie')->assertSee('Dla kogo?')->assertSee('Liderzy i liderki')
            ->assertDontSee('Szkic kursu');
    }

    public function test_elearning_bez_danych_nie_pokazuje_pustych_sekcji(): void
    {
        $this->useTemplate('elearning');

        $this->get('/')->assertOk()->assertDontSee('Dla kogo?')->assertDontSee('Eksperci')->assertDontSee('kursów na platformie')->assertDontSee('FAQ');
    }

    public function test_podcasts_pokazuje_najnowszy_odcinek_i_liste(): void
    {
        $this->useTemplate('podcasts', ['tagline' => 'Słuchaj z nami']);
        Podcast::create(['title' => 'Starszy odcinek', 'slug' => 'starszy', 'is_published' => true, 'published_at' => now()->subDays(5), 'episode_number' => 1]);
        Podcast::create(['title' => 'Najnowszy odcinek', 'slug' => 'nowy', 'is_published' => true, 'published_at' => now()->subDay(), 'episode_number' => 2]);
        Podcast::create(['title' => 'Nieopublikowany', 'slug' => 'nie', 'is_published' => false]);

        $this->get('/')->assertOk()
            ->assertSee('class="pc pc-header"', false)->assertSee('Słuchaj z nami')
            ->assertSee('Najnowszy odcinek')->assertSee('Starszy odcinek')->assertDontSee('Nieopublikowany');
    }

    public function test_inne_szablony_nie_dostaja_stylow_tych_szablonow(): void
    {
        $this->useTemplate('default');

        $this->get('/kontakt')->assertOk()->assertDontSee('template-elearning', false)->assertDontSee('el-header', false)->assertDontSee('pc-header', false);
    }
}
