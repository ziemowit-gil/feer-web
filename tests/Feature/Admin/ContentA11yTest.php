<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Content\ContentA11yChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentA11yTest extends TestCase
{
    use RefreshDatabase;

    private const BAD_HTML = '<h3>Start</h3><p><strong>Udział w projekcie</strong></p><p>Tekst</p><h2>Sekcja</h2><h4>Pod</h4>'
        .'<p>Regulamin: <a href="/r.pdf">kliknij tutaj</a> <a href="/x"><img src="/a.jpg"></a></p>'
        .'<table><tr><td>Imię</td><td>Wiek</td></tr></table><iframe src="https://www.youtube.com/embed/x"></iframe>'
        .'<p>- jeden</p><p>- dwa</p>';

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_checker_finds_typical_problems(): void
    {
        $rules = array_column(app(ContentA11yChecker::class)->check(self::BAD_HTML), 'rule');

        foreach (['heading_first', 'heading_skip', 'fake_heading', 'link_vague', 'link_empty', 'img_alt_missing', 'table_header', 'iframe_title', 'fake_list'] as $rule) {
            $this->assertContains($rule, $rules, "Brak reguły {$rule}.");
        }
    }

    public function test_correct_content_has_no_issues(): void
    {
        $html = '<h2>O nas</h2><p>Pomagamy. <a href="/regulamin.pdf">Regulamin konkursu (PDF)</a></p>'
            .'<h3>Zespół</h3><img src="/z.jpg" alt="Zespół fundacji na schodach"><img src="/o.png" alt="">'
            .'<table><tr><th>Imię</th></tr><tr><td>Ala</td></tr></table><ul><li>jeden</li><li>dwa</li></ul>'
            .'<iframe title="Film o fundacji" src="https://www.youtube.com/embed/x"></iframe>';

        $this->assertSame([], app(ContentA11yChecker::class)->check($html));
    }

    public function test_live_check_endpoint_returns_summary_and_list(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.content-a11y.check'), ['html' => self::BAD_HTML])
            ->assertOk()
            ->assertJsonPath('summary.errors', 5)
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'kliknij tutaj'))->etc());
    }

    public function test_site_report_lists_content_with_problems(): void
    {
        Page::create(['title' => 'Strona z problemami', 'slug' => 'zla', 'type' => 'standard', 'is_published' => true, 'content' => self::BAD_HTML]);
        Page::create(['title' => 'Dobra strona', 'slug' => 'dobra', 'type' => 'standard', 'is_published' => true, 'content' => '<h2>OK</h2><p>Treść</p>']);
        News::create(['title' => 'Aktualność bez alt', 'slug' => 'bez-alt', 'content' => '<p><img src="/a.jpg"></p>', 'is_published' => true, 'published_at' => now()]);

        $this->actingAs($this->admin())->get(route('admin.content-a11y.index'))
            ->assertOk()
            ->assertSee('Strona z problemami')
            ->assertSee('Aktualność bez alt')
            ->assertDontSee('Dobra strona');

        $this->actingAs($this->admin())->get(route('admin.content-a11y.index', ['regula' => 'img_alt_missing', 'typ' => 'news']))
            ->assertOk()
            ->assertSee('Aktualność bez alt')
            ->assertDontSee('Strona z problemami');
    }

    public function test_saving_a_page_reports_accessibility_in_status(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-a11y', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>']);

        $this->actingAs($this->admin())
            ->put(route('admin.podstrony.update', $page), [
                'title' => 'Strona', 'slug' => 'strona-a11y', 'type' => 'standard', 'is_published' => 1, 'stay' => 1, 'parent_id' => '', 'project_id' => '', 'order' => 0,
                'content' => '<h2>Sekcja</h2><h4>Pod</h4><p><a href="/x">kliknij tutaj</a></p>',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'Dostępność treści: 1 błąd i 1 uwaga'));

        $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))
            ->assertOk()
            ->assertSee('Dostępność treści')
            ->assertSee('Po H2 jest od razu H4', false);
    }

    public function test_saving_a_page_as_draft_unpublishes_it(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-szkic', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>']);

        $this->actingAs($this->admin())
            ->put(route('admin.podstrony.update', $page), [
                'title' => 'Strona', 'slug' => 'strona-szkic', 'type' => 'standard', 'is_published' => 1, 'draft' => 1,
                'parent_id' => '', 'project_id' => '', 'order' => 0, 'content' => '<p>x</p>',
            ])
            ->assertRedirect(route('admin.podstrony.edit', [$page, 'klasyczny' => 1]))
            ->assertSessionHas('status', fn ($status) => str_starts_with($status, 'Zapisano wersję roboczą'));

        $this->assertFalse((bool) $page->fresh()->is_published);
    }
}
