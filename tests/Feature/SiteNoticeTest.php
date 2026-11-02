<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ramka z komunikatem na stronie głównej i w Kontakcie (Ustawienia → Serwis). */
class SiteNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
    }

    private function notice(array $extra = []): void
    {
        SiteSetting::current()->update($extra + [
            'notice_enabled' => true, 'notice_title' => 'Urlop biura', 'notice_text' => 'Biuro nieczynne do 10 listopada.', 'notice_style' => 'info',
        ]);
    }

    public function test_hidden_by_default(): void
    {
        $this->get('/')->assertOk()->assertDontSee('site-notice', false);
        $this->get('/kontakt')->assertOk()->assertDontSee('site-notice', false);
    }

    public function test_shown_on_home_and_contact_only(): void
    {
        $this->notice();

        foreach (['/', '/kontakt'] as $url) {
            $this->get($url)->assertOk()->assertSee('site-notice', false)->assertSee('Urlop biura')->assertSee('Biuro nieczynne do 10 listopada.');
        }
        $this->get('/start')->assertOk()->assertDontSee('site-notice', false);
    }

    public function test_place_toggles_and_empty_text(): void
    {
        $this->notice(['notice_on_contact' => false]);
        $this->get('/')->assertSee('site-notice', false);
        $this->get('/kontakt')->assertDontSee('site-notice', false);

        $this->notice(['notice_text' => '  ']);
        $this->get('/')->assertDontSee('site-notice', false);
    }

    public function test_warning_style_and_escaping(): void
    {
        $this->notice(['notice_style' => 'warning', 'notice_text' => '<script>x</script>']);

        $this->get('/')->assertSee('amber-950', false)->assertDontSee('<script>x</script>', false);
    }
}
