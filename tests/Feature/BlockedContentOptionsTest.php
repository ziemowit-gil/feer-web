<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NavItem;
use App\Models\News;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kolejne grupy w Ustawienia → Moduły → „Typy treści i układy” (blocked_options). */
class BlockedContentOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->block([]);
    }

    private function block(array $blocked): void
    {
        if ($blocked) {
            SiteSetting::current()->update(['blocked_options' => $blocked]);
        }
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_nowe_grupy_sa_w_ustawieniach_a_opcje_awaryjne_zawsze_dostepne(): void
    {
        $groups = SiteSetting::blockableOptionGroups();
        foreach (['news_post_types', 'event_types', 'nav_item_types', 'content_block_types', 'side_nav_styles', 'bank_accounts_layouts'] as $group) {
            $this->assertArrayHasKey($group, $groups);
            $this->assertArrayHasKey($group, SiteSetting::BLOCKABLE_GROUP_LABELS);
        }

        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('name="allowed_options[event_types][]" value="webinar"', $html);
        $this->assertStringContainsString('Typy wpisów (aktualności)', $html);
    }

    public function test_zablokowany_typ_wpisu_znika_i_jest_odrzucany_ale_istniejacy_zostaje(): void
    {
        $this->block(['news_post_types' => ['review']]);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('admin.newsy.create'))->assertOk()->getContent();
        $this->assertStringNotContainsString('name="post_type" value="review"', $html);
        $this->assertStringContainsString('name="post_type" value="announcement"', $html);

        $this->actingAs($admin)->post(route('admin.newsy.store'), [
            'title' => 'Wpis', 'published_at' => now()->toDateTimeString(), 'post_type' => 'review', 'type_data' => ['tool_name' => 'X'],
        ])->assertSessionHasErrors('post_type');

        $news = News::create(['title' => 'Test', 'slug' => 'test', 'content' => '<p>x</p>', 'published_at' => now(), 'is_published' => true, 'post_type' => 'review']);
        $this->flushSession();
        $this->actingAs($admin)->get(route('admin.newsy.edit', $news))->assertOk()->assertSee('name="post_type" value="review"', false);
    }

    public function test_zablokowany_rodzaj_wydarzenia_jest_odrzucany_przy_nowym(): void
    {
        $this->block(['event_types' => ['webinar']]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.wydarzenia.create'))->assertOk()
            ->assertDontSee('<option value="webinar"', false)
            ->assertSee('<option value="warsztat"', false);

        $this->actingAs($admin)->post(route('admin.wydarzenia.store'), [
            'title' => 'Webinar WCAG', 'lead' => 'Wprowadzenie.', 'type' => 'webinar', 'mode' => 'zdalnie',
            'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'audience' => 'brand',
        ])->assertSessionHasErrors('type');
        $this->assertSame(0, Event::count());
    }

    public function test_zablokowany_typ_pozycji_menu_odrzucany_dla_nowej_dozwolony_dla_istniejacej(): void
    {
        $this->block(['nav_item_types' => ['faq']]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.pozycje-menu.store'), [
            'label' => 'FAQ', 'type' => 'faq', 'location' => 'main', 'is_active' => '1',
        ])->assertSessionHasErrors('type');

        $item = NavItem::create(['label' => 'FAQ', 'type' => 'faq', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        $this->actingAs($admin)->put(route('admin.pozycje-menu.update', $item), [
            'label' => 'Pytania', 'type' => 'faq', 'location' => 'main', 'is_active' => '1',
        ])->assertSessionDoesntHaveErrors('type');
    }

    public function test_nie_mozna_utworzyc_bloku_wylaczonego_typu(): void
    {
        $this->block(['content_block_types' => ['pricing']]);

        $this->actingAs($this->admin())->postJson(route('admin.bloki-tresci.store'), [
            'type' => 'pricing', 'name' => 'Cennik', 'data' => ['rows' => [['item' => 'A', 'price' => '1 zł']]],
        ])->assertStatus(422);
    }

    public function test_dostepne_opcje_zachowuja_biezaca_wartosc(): void
    {
        $this->block(['side_nav_styles' => ['tree', 'tiles']]);
        $s = SiteSetting::current();

        $this->assertArrayNotHasKey('tree', $s->availableOptions('side_nav_styles', \App\Models\Page::SIDE_NAV_STYLES));
        $this->assertArrayHasKey('tree', $s->availableOptions('side_nav_styles', \App\Models\Page::SIDE_NAV_STYLES, 'tree'));
        $this->assertSame(['tiles'], $s->blockedOptionsExcept('side_nav_styles', 'tree'));
    }
}
