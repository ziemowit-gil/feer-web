<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Asystent AI (Claude API): wyłączony bez klucza, wywołanie API z serwera, czyszczenie HTML, przycisk w edytorze. */
class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_disabled_without_setting(): void
    {
        $this->reset();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->postJson(route('admin.asystent-ai'), ['action' => 'improve', 'text' => 'Tekst'])->assertNotFound();
    }

    public function test_calls_claude_and_cleans_html(): void
    {
        $this->reset();
        SiteSetting::current()->update(['ai_enabled' => true, 'ai_api_key' => 'sk-ant-test', 'ai_model' => 'claude-sonnet-5-5']);
        $this->reset();
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => "```html\n<p onclick=\"x()\">Poprawiony tekst.</p><script>alert(1)</script>\n```"]]])]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->postJson(route('admin.asystent-ai'), ['action' => 'improve', 'text' => '<p>Tekst z bledem.</p>'])
            ->assertOk()->assertJson(['result' => '<p>Poprawiony tekst.</p>', 'html' => true]);

        Http::assertSent(fn ($r) => $r->url() === 'https://api.anthropic.com/v1/messages'
            && $r->header('x-api-key')[0] === 'sk-ant-test'
            && $r['model'] === 'claude-sonnet-5-5'
            && str_contains($r['messages'][0]['content'], 'Tekst z bledem.'));

        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'is_published' => true]);
        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('Asystent AI');
    }

    public function test_plain_text_actions_and_api_errors(): void
    {
        $this->reset();
        SiteSetting::current()->update(['ai_enabled' => true, 'ai_api_key' => 'sk-ant-test']);
        $this->reset();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Http::fakeSequence('api.anthropic.com/*')
            ->push(['content' => [['type' => 'text', 'text' => "Tytuł pierwszy\nTytuł drugi"]]])
            ->push(['error' => 'bad key'], 401);
        $this->actingAs($admin)->postJson(route('admin.asystent-ai'), ['action' => 'title', 'text' => 'Treść'])
            ->assertOk()->assertJson(['result' => "Tytuł pierwszy\nTytuł drugi", 'html' => false]);

        $this->actingAs($admin)->postJson(route('admin.asystent-ai'), ['action' => 'improve', 'text' => 'Treść'])
            ->assertStatus(502)->assertJsonFragment(['message' => 'Klucz API asystenta jest nieprawidłowy — sprawdź go w Ustawienia → Treści.']);
    }
}
