<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_kontakt_bez_strony_kontaktowej_dziala_jak_dotad(): void
    {
        $this->get('/kontakt')->assertOk();
    }

    public function test_kontakt_kieruje_na_strone_typu_kontakt_a_ta_renderuje_formularz(): void
    {
        $page = Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => true]);

        $this->get('/kontakt')->assertRedirect($page->publicUrl());
        $this->get($page->publicUrl())->assertOk()->assertSee('Formularz kontaktowy', false);
    }

    public function test_nieopublikowana_strona_nie_przejmuje_adresu(): void
    {
        Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => false]);

        $this->get('/kontakt')->assertOk();
    }
}
