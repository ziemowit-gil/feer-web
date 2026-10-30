<?php

namespace Tests\Feature;

use App\Mcp\Servers\FeerServer;
use App\Mcp\Tools\CreateNewsDraftTool;
use App\Mcp\Tools\CreatePageDraftTool;
use App\Mcp\Tools\CreateProjectDraftTool;
use App\Mcp\Tools\SearchProjectsTool;
use App\Models\Category;
use App\Models\Project;
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

    public function test_project_search_and_draft(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cat = Category::create(['name' => 'Kat', 'slug' => 'kat']);
        Project::create(['title' => 'Działanie opublikowane', 'slug' => 'dz-pub', 'category_id' => $cat->id, 'is_published' => true]);
        Project::create(['title' => 'Działanie szkic', 'slug' => 'dz-szkic', 'category_id' => $cat->id, 'is_published' => false]);

        FeerServer::actingAs($admin)->tool(SearchProjectsTool::class, ['query' => 'Działanie'])
            ->assertOk()->assertSee('dz-pub')->assertDontSee('dz-szkic');

        Sanctum::actingAs($admin, ['mcp:write']);
        FeerServer::actingAs($admin)->tool(CreateProjectDraftTool::class, ['title' => 'Nowe z MCP', 'content' => 'Treść'])->assertOk();
        $this->assertFalse(Project::where('title', 'Nowe z MCP')->firstOrFail()->is_published);
    }

    public function test_page_draft_under_parent_is_hidden(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $parent = Page::create(['title' => 'Rodzic', 'slug' => 'rodzic', 'is_published' => true]);
        Sanctum::actingAs($admin, ['mcp:write']);

        FeerServer::actingAs($admin)->tool(CreatePageDraftTool::class, ['title' => 'Podstrona z MCP', 'content' => 'Tekst', 'parent_slug' => 'rodzic'])->assertOk();

        $page = Page::where('title', 'Podstrona z MCP')->firstOrFail();
        $this->assertFalse($page->is_published);
        $this->assertSame($parent->id, $page->parent_id);
    }

    public function test_admin_creates_and_revokes_token_in_gui(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.mcp.store'), ['name' => 'laptop', 'write' => 1])
            ->assertRedirect(route('admin.mcp.index'))->assertSessionHas('new_token');

        $token = $admin->tokens()->firstOrFail();
        $this->assertSame(['mcp:read', 'mcp:write'], $token->abilities);

        $this->actingAs($admin)->get(route('admin.mcp.index'))->assertOk()->assertSee('laptop');
        $this->actingAs($admin)->delete(route('admin.mcp.destroy', $token->id));
        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_non_admin_cannot_open_mcp_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]))
            ->get(route('admin.mcp.index'))->assertForbidden();
    }
}
