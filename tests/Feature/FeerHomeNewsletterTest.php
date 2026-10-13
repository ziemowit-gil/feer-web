<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Strona główna FEER: zwarty zapis na newsletter (e-mail, zabezpieczenie, zgoda) działa bez przechodzenia na inną stronę. */
class FeerHomeNewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_compact_form_subscribes(): void
    {
        Mail::fake();
        Notification::fake();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="feer-nl-heading"', $html);

        preg_match('~<section class="bg-brand-light py-12" aria-labelledby="feer-nl-heading">.*?</section>~s', $html, $m);
        $section = $m[0];
        preg_match_all('~<input type="hidden" name="([^"]+)" value="([^"]*)"~', $section, $hidden, PREG_SET_ORDER);
        $data = [];
        foreach ($hidden as [, $name, $value]) {
            str_ends_with($name, '[]') ? $data[substr($name, 0, -2)][] = $value : $data[$name] = html_entity_decode($value);
        }
        preg_match('~Ile wynosi (\d+) \+ (\d+)\?~', $section, $q);
        $data[\App\Support\SpamGuard::ANSWER_FIELD] = (string) ((int) $q[1] + (int) $q[2]);
        $data['email'] = 'osoba@example.org';
        $data['consent'] = '1';

        $this->travel(10)->seconds();
        $this->post(route('newsletter.subscribe'), $data)->assertSessionHasNoErrors();
        $this->assertTrue(\App\Models\Subscriber::where('source', 'home_compact')->exists(), 'Zapis ze strony głównej trafił do subskrybentów.');
    }
}
