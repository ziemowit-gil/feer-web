<?php

namespace Tests\Feature;

use App\Mcp\Servers\FeerServer;
use App\Mcp\Tools\CreateNewsDraftTool;
use App\Mcp\Tools\SearchPagesTool;
use App\Models\News;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Zdalny serwer MCP: dostęp tylko dla administratora z tokenem mcp:read, narzędzia tylko do odczytu. */
class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private const PING = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'];

    public function test_rejects_request_without_token(): void
    {
        $this->postJson('/mcp/feer', self::PING)->assertUnauthorized();
    }

    public function test_rejects_non_admin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]), ['mcp:read']);

        $this->postJson('/mcp/feer', self::PING)->assertForbidden();
    }

    public function test_rejects_token_without_mcp_read_ability(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]), ['inne']);

        $this->postJson('/mcp/feer', self::PING)->assertForbidden();
    }

    public function test_search_returns_only_published_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Page::create(['title' => 'Fundacja testowa', 'slug' => 'fundacja-testowa', 'content' => '<p>Treść</p>', 'is_published' => true]);
        Page::create(['title' => 'Fundacja szkic', 'slug' => 'fundacja-szkic', 'content' => '<p>x</p>', 'is_published' => false]);

        FeerServer::actingAs($admin)->tool(SearchPagesTool::class, ['query' => 'Fundacja'])
            ->assertOk()
            ->assertSee('fundacja-testowa')
            ->assertDontSee('fundacja-szkic');
    }

    public function test_creates_unpublished_news_draft_with_escaped_content(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin, ['mcp:read', 'mcp:write']);

        FeerServer::actingAs($admin)->tool(CreateNewsDraftTool::class, [
            'title' => 'Szkic z MCP', 'content' => "Pierwszy <b>akapit</b>\n\nDrugi",
        ])->assertOk()->assertSee('szkic');

        $news = News::where('title', 'Szkic z MCP')->firstOrFail();
        $this->assertFalse($news->is_published);
        $this->assertStringContainsString('&lt;b&gt;', $news->content);
        $this->assertSame($admin->id, $news->created_by);
    }

    public function test_draft_requires_write_ability(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin, ['mcp:read']);

        FeerServer::actingAs($admin)->tool(CreateNewsDraftTool::class, ['title' => 'Nie', 'content' => 'x'])
            ->assertHasErrors();
        $this->assertSame(0, News::count());
    }
}
