<?php

namespace Tests\Feature;

use App\Models\GdprClause;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GdprClauseImporter;
use App\Support\SafeHtml;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GdprClauseImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        config(['szo.url' => 'https://szo.test']);
    }

    /** Aktualna „odpowiedź SZO” — jedna atrapa HTTP czyta ją przy każdym żądaniu. */
    private array $remote = [];

    private bool $faked = false;

    private function fakeSzo(array $clauses): void
    {
        $this->remote = $clauses;
        if ($this->faked) {
            return;
        }
        $this->faked = true;
        Http::fake(['szo.test/klauzule.json' => fn () => Http::response(['ok' => true, 'count' => count($this->remote), 'clauses' => $this->remote])]);
    }

    private function clause(string $slug, string $lang = 'pl', array $over = []): array
    {
        return array_merge([
            'slug' => $slug, 'lang' => $lang, 'title' => "Klauzula {$slug}", 'version' => 1,
            'updated_at' => '2026-09-20T10:00:00+02:00',
            'url' => "https://szo.test/klauzula/{$slug}", 'pdf_url' => "https://szo.test/klauzula/{$slug}.pdf",
            'html' => '<h2>Administrator</h2><p>Treść <strong>ważna</strong>.</p>',
        ], $over);
    }

    public function test_import_adds_updates_and_removes(): void
    {
        $this->fakeSzo([$this->clause('kontakt'), $this->clause('szkolenia'), $this->clause('kontakt', 'en')]);
        $s = app(GdprClauseImporter::class)->import();
        $this->assertSame([3, 0, 0, 3], [$s['added'], $s['updated'], $s['removed'], $s['total']]);

        // Lokalne ustawienia przeżywają import; zmieniona treść się aktualizuje; wycofana klauzula znika
        GdprClause::where('slug', 'szkolenia')->update(['is_visible' => false, 'sort_order' => 50]);
        $this->fakeSzo([$this->clause('kontakt', 'pl', ['version' => 2, 'title' => 'Nowy tytuł']), $this->clause('szkolenia')]);
        $s = app(GdprClauseImporter::class)->import();
        $this->assertSame([0, 1, 1, 1], [$s['added'], $s['updated'], $s['unchanged'], $s['removed']]);

        $sz = GdprClause::where('slug', 'szkolenia')->first();
        $this->assertFalse($sz->is_visible);
        $this->assertSame(50, $sz->sort_order);
        $this->assertSame('Nowy tytuł', GdprClause::where('slug', 'kontakt')->where('lang', 'pl')->value('title'));
        $this->assertNull(GdprClause::where('lang', 'en')->first());
    }

    public function test_empty_remote_list_does_not_wipe_local_copy(): void
    {
        $this->fakeSzo([$this->clause('kontakt')]);
        app(GdprClauseImporter::class)->import();

        $this->fakeSzo([]);
        $this->expectException(RuntimeException::class);
        try {
            app(GdprClauseImporter::class)->import();
        } finally {
            $this->assertSame(1, GdprClause::count());
        }
    }

    public function test_szo_error_is_reported(): void
    {
        Http::fake(['szo.test/*' => Http::response('boom', 500)]);
        $this->expectExceptionMessage('HTTP 500');
        app(GdprClauseImporter::class)->import();
    }

    public function test_html_is_sanitized(): void
    {
        $dirty = '<p onclick="x()">Tekst<script>alert(1)</script></p><iframe src="//evil"></iframe>'
            . '<a href="javascript:alert(1)">zły</a> <a href="https://feer.org.pl" style="color:red">dobry</a><div><em>ok</em></div>';
        $clean = SafeHtml::clean($dirty);

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('style=', $clean);
        $this->assertStringContainsString('<a href="https://feer.org.pl" rel="noopener">dobry</a>', $clean);
        $this->assertStringContainsString('<em>ok</em>', $clean);
        $this->assertStringContainsString('zły', $clean);
        $this->assertSame('<h4>A</h4><h5>B</h5>', SafeHtml::demoteHeadings('<h2>A</h2><h3>B</h3>', 2));
    }

    public function test_shortcode_renders_visible_clauses_in_order(): void
    {
        $this->fakeSzo([$this->clause('b-druga'), $this->clause('a-pierwsza'), $this->clause('ukryta'), $this->clause('kontakt', 'en')]);
        app(GdprClauseImporter::class)->import();
        GdprClause::where('slug', 'a-pierwsza')->update(['sort_order' => 0]);
        GdprClause::where('slug', 'ukryta')->update(['is_visible' => false]);

        $html = ShortcodeParser::render('<p>Wstęp</p><p>[klauzule-rodo]</p>');

        $this->assertStringContainsString('Klauzule informacyjne', $html);
        $this->assertLessThan(strpos($html, 'Klauzula b-druga'), strpos($html, 'Klauzula a-pierwsza'));
        $this->assertStringNotContainsString('Klauzula ukryta', $html);
        $this->assertStringNotContainsString('<p>[klauzule-rodo]</p>', $html);
        $this->assertStringContainsString('<h4>Administrator</h4>', $html);
        $this->assertStringContainsString('https://szo.test/klauzula/a-pierwsza.pdf', $html);

        $en = ShortcodeParser::render('[klauzule-rodo:en]');
        $this->assertStringContainsString('Information clauses', $en);
        $this->assertTrue(ShortcodeParser::has('x [klauzule-rodo] y'));
        $this->assertTrue(ShortcodeParser::has('[formularz:kontakt]'));
        $this->assertFalse(ShortcodeParser::has('klauzule-rodo bez nawiasów'));
    }

    public function test_page_shows_clauses_and_admin_can_import_and_hide(): void
    {
        $this->fakeSzo([$this->clause('kontakt')]);
        Page::create(['title' => 'Dane osobowe', 'slug' => 'rodo', 'content' => '<p>Intro</p><p>[klauzule-rodo]</p>', 'is_published' => true, 'type' => 'standard']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.klauzule-rodo.index'))->assertOk()->assertSee('Jeszcze nie importowano');
        $this->actingAs($admin)->post(route('admin.klauzule-rodo.import'))->assertRedirect()->assertSessionHas('status');

        $this->get('/rodo')->assertOk()->assertSee('Klauzula kontakt');

        $c = GdprClause::first();
        $this->actingAs($admin)->put(route('admin.klauzule-rodo.update'), ['clauses' => [$c->id => ['sort_order' => 3, 'is_visible' => '0']]])
            ->assertRedirect()->assertSessionHas('status');
        $this->assertFalse($c->fresh()->is_visible);
        $this->get('/rodo')->assertOk()->assertDontSee('Klauzula kontakt');
    }

    public function test_non_admin_cannot_import(): void
    {
        $this->fakeSzo([$this->clause('kontakt')]);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->actingAs($user)->post(route('admin.klauzule-rodo.import'))->assertForbidden();
        $this->assertSame(0, GdprClause::count());
    }
}
