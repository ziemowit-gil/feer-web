<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\Edition;

/**
 * Blog dostosowany do innej witryny niż FEER (strona Kreatywnych): nazwa i wstęp z konfiguracji zamiast „Wiem FEER”,
 * sekcja „Blog” na stronie głównej szablonu „kreatywni”, przycisk „Import z Worda” w edytorze wpisu (wersja płatna
 * albo funkcja włączona wyjątkowo przez EDITION_FEATURES).
 */
class BlogKreatywniTest extends BlogTestCase
{
    protected function tearDown(): void
    {
        Edition::flush();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    public function test_nazwa_i_wstep_bloga_z_konfiguracji(): void
    {
        config(['blog.name' => 'Blog']);
        $this->article('Jak zrobić świecę');

        $page = $this->get('/wiem')->assertOk();
        $page->assertSee('<h1 class="text-4xl font-extrabold text-ink md:text-5xl">Blog</h1>', false)
            ->assertSee('Najnowsze wpisy, relacje i porady.')
            ->assertSee('aria-label="Kolumna boczna Blog"', false)
            ->assertDontSee('Wiem FEER');

        config(['blog.name' => '']);
        $this->get('/wiem')->assertOk()->assertSee('Wiem FEER')->assertSee('Artykuły o dostępności cyfrowej');
    }

    public function test_sekcja_blog_na_stronie_glownej_kreatywnych(): void
    {
        SiteSetting::current()->forceFill(['site_template' => 'kreatywni'])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->article('Świece sojowe krok po kroku', ['excerpt' => 'Prosty sposób na zimowe wieczory.']);

        $home = $this->get('/')->assertOk();
        $home->assertSee('id="blog"', false)
            ->assertSee('Z naszego bloga')
            ->assertSee('Świece sojowe krok po kroku')
            ->assertSee('Prosty sposób na zimowe wieczory.')
            ->assertSee(route('blog.index'), false);
    }

    public function test_import_z_worda_w_edytorze_wpisu_i_wyjatek_w_wersji_darmowej(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.wiem-feer.create'))->assertOk()
            ->assertSee('Import z Worda')->assertSee('id="blog-docx-input"', false)
            ->assertDontSee('data-docx-locked="1"', false);

        config(['editions.forced' => 'free']);
        Edition::flush();
        $this->get(route('admin.wiem-feer.create'))->assertOk()->assertSee('data-docx-locked="1"', false);

        config(['editions.extra_features' => 'docx_import']);
        Edition::flush();
        $this->assertTrue(Edition::allows('docx_import'));
        $this->assertFalse(Edition::allows('newsletter'), 'wyjątek dotyczy tylko wymienionej funkcji');
        $this->get(route('admin.wiem-feer.create'))->assertOk()->assertDontSee('data-docx-locked="1"', false);
    }
}
