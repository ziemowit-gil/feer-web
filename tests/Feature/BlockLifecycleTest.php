<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use App\Support\BlockSchedule;
use App\Support\BlockSummary;
use App\Support\ShortcodeParser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edytor blokowy — cykl życia bloków: harmonogram („Pokaż od” / „Ukryj od”), jeden poziom zagnieżdżenia,
 * bloki typowane (zakładki, FAQ, wezwanie do działania, osoby, karty), bloki wspólne edytowane w edytorze
 * i historia wersji z drzewem bloków.
 */
class BlockLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function requireBlockEditor(): void
    {
        if (! SiteSetting::current()->isModuleEnabled('block_editor')) {
            $this->markTestSkipped('Moduł edytora blokowego wyłączony w ustawieniach testowych.');
        }
    }

    // ── Harmonogram ────────────────────────────────────────────────────────

    public function test_scheduled_block_is_shown_only_inside_its_window(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'paragraph', 'attrs' => ['text' => 'Zawsze']],
            ['type' => 'paragraph', 'attrs' => ['text' => 'Nabór trwa'], 'from' => '2026-12-01T08:00', 'until' => '2026-12-15T00:00'],
        ]);
        $this->assertStringContainsString('<!--be-okno:1 od=2026-12-01T08:00 do=2026-12-15T00:00--><p>Nabór trwa</p><!--/be-okno:1-->', $html);

        // Czas polski: 1 grudnia 07:30 w Warszawie to jeszcze przed oknem (06:30 UTC).
        Carbon::setTestNow(Carbon::parse('2026-12-01 06:30', 'UTC'));
        $before = ShortcodeParser::render($html);
        $this->assertStringContainsString('Zawsze', $before);
        $this->assertStringNotContainsString('Nabór trwa', $before);

        Carbon::setTestNow(Carbon::parse('2026-12-01 07:00', 'UTC')); // 08:00 w Warszawie
        $during = ShortcodeParser::render($html);
        $this->assertStringContainsString('<p>Nabór trwa</p>', $during);
        $this->assertStringNotContainsString('be-okno', $during);

        Carbon::setTestNow(Carbon::parse('2026-12-15 12:00', 'UTC'));
        $this->assertStringNotContainsString('Nabór trwa', ShortcodeParser::render($html));
    }

    public function test_schedule_values_are_validated_and_described(): void
    {
        $blocks = BlockRenderer::normalize([
            ['type' => 'paragraph', 'attrs' => ['text' => 'A'], 'from' => '2026-02-30T10:00', 'until' => 'jutro'],
            ['type' => 'paragraph', 'attrs' => ['text' => 'B'], 'from' => '2026-03-01 10:00:59'],
        ]);
        $this->assertArrayNotHasKey('from', $blocks[0]);
        $this->assertArrayNotHasKey('until', $blocks[0]);
        $this->assertSame('2026-03-01T10:00', $blocks[1]['from']);

        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00', 'UTC'));
        $this->assertSame('scheduled', BlockSchedule::stateOf($blocks[1]));
        $this->assertSame('pokaże się od 1.03.2026 10:00', BlockSummary::describe($blocks[1])['schedule']);
        $this->assertNull(BlockSummary::describe($blocks[0])['schedule']);
    }

    // ── Jeden poziom zagnieżdżenia ─────────────────────────────────────────

    public function test_containers_nested_in_containers_are_unwrapped_without_losing_content(): void
    {
        $raw = [
            ['type' => 'columns', 'inner' => [
                ['type' => 'column', 'inner' => [
                    ['type' => 'details', 'attrs' => ['summary' => 'Pytanie <b>1</b>'], 'hidden' => false, 'inner' => [
                        ['type' => 'callout', 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'Głęboko']]]],
                    ]],
                ]],
                ['type' => 'column', 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'B']]]],
            ]],
        ];
        $this->assertSame(2, BlockRenderer::nestedContainers($raw));

        $blocks = BlockRenderer::normalize($raw);
        $first = $blocks[0]['inner'][0]['inner'];
        $this->assertSame(['paragraph', 'paragraph'], array_column($first, 'type'));
        $this->assertSame('<strong>Pytanie &lt;b&gt;1&lt;/b&gt;</strong>', $first[0]['attrs']['text']);
        $this->assertSame('Głęboko', $first[1]['attrs']['text']);
        $this->assertSame(0, BlockRenderer::nestedContainers($blocks));
    }

    // ── Bloki typowane ─────────────────────────────────────────────────────

    public function test_typed_blocks_render_semantic_markup(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'tabs', 'attrs' => ['level' => 3], 'inner' => [
                ['type' => 'tab', 'attrs' => ['label' => 'Program'], 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'Dzień 1']]]],
                ['type' => 'tab', 'attrs' => ['label' => ''], 'inner' => []],
                ['type' => 'paragraph', 'attrs' => ['text' => 'nie zakładka']],
            ]],
            ['type' => 'faq', 'attrs' => ['title' => 'FAQ', 'items' => [['q' => 'Ile kosztuje?', 'a' => 'Nic.<br><br>Naprawdę.'], ['q' => '', 'a' => 'bez pytania']]]],
            ['type' => 'cta', 'attrs' => ['title' => 'Dołącz', 'text' => 'Pomóż nam', 'label' => 'Zgłoś się', 'url' => 'javascript:alert(1)']],
            ['type' => 'people', 'attrs' => ['items' => [['name' => 'Anna <i>N</i>', 'role' => 'Prezeska', 'email' => 'zly-adres', 'phone' => '601 350 487']]]],
            ['type' => 'cards', 'attrs' => ['columns' => 2, 'items' => [['title' => 'Szkolenia', 'text' => 'Opis', 'url' => '/szkolenia', 'image' => '/storage/a.jpg', 'alt' => '']]]],
        ]);

        $this->assertStringContainsString('<div class="content-tabs" data-tabs><section class="content-tab"><h3 class="content-tab-title">Program</h3>', $html);
        $this->assertStringContainsString('<h3 class="content-tab-title">Zakładka 2</h3>', $html);
        $this->assertStringNotContainsString('nie zakładka', $html);
        $this->assertStringContainsString('<summary>Ile kosztuje?</summary><div class="content-details-body"><p>Nic.</p><p>Naprawdę.</p></div>', $html);
        $this->assertStringNotContainsString('bez pytania', $html);
        // Przycisk bez bezpiecznego adresu nie trafia na stronę, nagłówek i tekst tak.
        $this->assertStringContainsString('<h2 class="content-cta-title">Dołącz</h2><p class="content-cta-text">Pomóż nam</p></div>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<h3 class="content-person-name">Anna N</h3>', $html);
        $this->assertStringNotContainsString('zly-adres', $html);
        $this->assertStringContainsString('<a href="tel:601350487">601 350 487</a>', $html);
        $this->assertStringContainsString('<ul class="content-cards content-cards--cols-2" role="list">', $html);
        $this->assertStringContainsString('<a href="/szkolenia" class="content-card-link">Szkolenia</a>', $html);
    }

    // ── Bloki wspólne ──────────────────────────────────────────────────────

    public function test_editor_saves_a_block_as_shared_and_edits_it_in_the_block_editor(): void
    {
        $this->requireBlockEditor();
        $admin = $this->admin();

        $res = $this->actingAs($admin)->postJson(route('admin.edytor-blokowy.blok-wspolny'), [
            'name' => 'Dane do przelewu',
            'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'Konto 00 1111'], 'until' => '2030-01-01T00:00']],
        ])->assertOk()->json();

        $shared = ContentBlock::findOrFail($res['id']);
        $this->assertTrue($shared->isShared());
        $this->assertSame('[blok:'.$shared->id.']', $res['code']);
        // Harmonogram zostaje przy wstawce na stronie, nie w bloku wspólnym.
        $this->assertArrayNotHasKey('until', $shared->data['blocks'][0]);

        $this->actingAs($admin)->get(route('admin.edytor-blokowy.edit', ['shared', $shared->id]))
            ->assertOk()->assertSee('Konto 00 1111', false)->assertSee('"shared":{"id":'.$shared->id, false);

        $this->actingAs($admin)->putJson(route('admin.edytor-blokowy.update', ['shared', $shared->id]), [
            'title' => 'Dane do przelewu (nowe)',
            'meta_description' => null,
            'blocks' => [
                ['type' => 'paragraph', 'attrs' => ['text' => 'Konto 22 3333']],
                ['type' => 'shortcode', 'attrs' => ['code' => '[blok:'.$shared->id.']']],
            ],
        ])->assertOk();

        $shared->refresh();
        $this->assertSame('Dane do przelewu (nowe)', $shared->name);
        $this->assertCount(1, $shared->data['blocks'], 'wstawka bloku wspólnego w bloku wspólnym jest pomijana');
        $this->assertSame(2, $shared->revisions()->count());

        // Zmiana widać na stronie z wstawką, z harmonogramem wstawki.
        $this->assertStringContainsString('Konto 22 3333', ShortcodeParser::render('<p>[blok:'.$shared->id.']</p>'));

        // Przywrócenie poprzedniej wersji wraca do starych bloków.
        $old = $shared->revisions()->get()->sortBy('id')->first();
        $this->actingAs($admin)->post(route('admin.historia.restore', ['shared_block', $shared->id, $old->id]))->assertRedirect();
        $this->assertSame('Konto 00 1111', strip_tags($shared->fresh()->data['blocks'][0]['attrs']['text']));
    }

    public function test_builder_blocks_are_not_opened_as_shared_blocks(): void
    {
        $this->requireBlockEditor();
        $cta = ContentBlock::create(['type' => 'cta', 'name' => 'CTA', 'data' => ['buttons' => []]]);

        $this->actingAs($this->admin())->get(route('admin.edytor-blokowy.edit', ['shared', $cta->id]))->assertNotFound();
        $this->assertSame(0, $cta->revisions()->count());
    }

    // ── Historia z drzewem bloków ──────────────────────────────────────────

    public function test_page_revisions_keep_blocks_and_restore_them(): void
    {
        $this->requireBlockEditor();
        $admin = $this->admin();
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-historia', 'content' => '', 'is_published' => true]);

        $save = fn (string $text) => $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Strona', 'meta_description' => null,
            'blocks' => [['type' => 'faq', 'attrs' => ['items' => [['q' => $text, 'a' => 'Odp']]]]],
        ])->assertOk();
        $save('Pierwsze pytanie');
        $save('Drugie pytanie');

        $first = $page->revisions()->get()->first(fn ($r) => str_contains((string) $r->data['content'], 'Pierwsze pytanie'));
        $this->assertNotNull($first);
        $this->assertSame('faq', $first->data['blocks'][0]['type']);

        $json = $this->actingAs($admin)->getJson(route('admin.historia.json', ['page', $page->id]))->assertOk()->json();
        $this->assertSame('faq', collect($json)->firstWhere('id', $first->id)['blocks'][0]['type']);

        $this->actingAs($admin)->post(route('admin.historia.restore', ['page', $page->id, $first->id]))->assertRedirect();
        $page->refresh();
        $this->assertIsArray($page->blocks, 'przywrócenie wersji nie zeruje bloków');
        $this->assertSame('Pierwsze pytanie', $page->blocks[0]['attrs']['items'][0]['q']);
        $this->assertStringContainsString('Pierwsze pytanie', $page->content);
    }
}
