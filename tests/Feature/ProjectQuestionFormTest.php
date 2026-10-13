<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Dedykowany formularz „Zadaj pytanie” do koordynatora działania. */
class ProjectQuestionFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_form_saves_and_sends_to_coordinator(): void
    {
        Mail::fake();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $cat = Category::create(['name' => 'Kat', 'slug' => 'kat']);
        $project = Project::create(['category_id' => $cat->id, 'title' => 'Akademia', 'slug' => 'akademia', 'is_published' => true,
            'coordinator_name' => 'Ewelina Frączek', 'coordinator_email' => 'ewelina.fraczek@feer.org.pl', 'show_coordinator' => true]);

        $html = $this->get('/dzialania/akademia/pytanie')->assertOk()->getContent();
        $this->assertStringContainsString('Ewelina Frączek', $html);
        preg_match('~Ile wynosi (\d+) \+ (\d+)\?~', $html, $q);
        preg_match('~name="' . preg_quote(\App\Support\SpamGuard::TOKEN_FIELD, '~') . '" value="([^"]+)"~', $html, $t);

        $this->travel(10)->seconds();
        $this->post('/dzialania/akademia/pytanie', [
            'name' => 'Anna Testowa', 'email' => 'anna@example.org', 'message' => 'Czy są miejsca?', 'rodo_consent' => '1',
            \App\Support\SpamGuard::TOKEN_FIELD => $t[1],
            \App\Support\SpamGuard::ANSWER_FIELD => (string) ((int) $q[1] + (int) $q[2]),
        ])->assertRedirect(route('projects.show', $project));

        $msg = ContactMessage::where('email', 'anna@example.org')->first();
        $this->assertNotNull($msg);
        $this->assertSame('ewelina.fraczek@feer.org.pl', $msg->coordinator_email);
        $this->assertSame('Pytanie o działanie: Akademia', $msg->subject);
        Mail::assertSent(ContactMessageMail::class, fn ($m) => $m->hasTo('ewelina.fraczek@feer.org.pl'));
    }

    public function test_question_button_and_form_without_coordinator(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $cat = Category::create(['name' => 'Kat', 'slug' => 'kat']);
        Project::create(['category_id' => $cat->id, 'title' => 'Bez osoby', 'slug' => 'bez-osoby', 'is_published' => true, 'show_coordinator' => false]);

        $this->get('/dzialania/bez-osoby')->assertOk()->assertSee('Zadaj pytanie');
        $this->get('/dzialania/bez-osoby/pytanie')->assertOk();
    }
}
