<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsFormTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function news(): News
    {
        return News::create(['title' => 'Pierwsza aktualność', 'slug' => 'pierwsza', 'content' => '<p>Treść</p>', 'is_published' => true, 'published_at' => now()]);
    }

    public function test_formularz_ma_zakladki_i_przypiety_pasek_zapisu(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.newsy.create'))->assertOk()->getContent();

        foreach (['Ogólne', 'Zdjęcie i wygląd', 'Kategoryzacja', 'Publikacja', 'SEO'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('role="tablist"', $html);
        foreach (['ogolne', 'obraz', 'kategorie', 'publikacja', 'seo'] as $key) {
            $this->assertStringContainsString('data-tab-panel="'.$key.'"', $html);
        }
        $this->assertStringContainsString('form="news-form" name="after" value="stay"', $html);
        $this->assertStringContainsString('form="news-form" name="after" value="close"', $html);
        $this->assertStringContainsString('position:sticky', $html);
        $this->assertStringContainsString('id="news-form"', $html);
    }

    public function test_klonuj_i_usun_sa_poza_glownym_formularzem(): void
    {
        $news = $this->news();
        $html = $this->actingAs($this->admin())->get(route('admin.newsy.edit', $news))->assertOk()->getContent();

        $open = strpos($html, 'id="news-form"');
        $close = strpos($html, '</form>', $open);
        $mainForm = substr($html, $open, $close - $open);

        $this->assertStringNotContainsString('<form', $mainForm, 'W głównym formularzu nie może być zagnieżdżonych <form>.');
        $this->assertStringContainsString(route('admin.newsy.klonuj', $news), $html);
        $this->assertStringContainsString(route('admin.newsy.destroy', $news), $html);
        $this->assertStringContainsString('id="tab-etr"', $html);
    }

    public function test_zapisz_zostaje_na_rekordzie_a_zapisz_i_zamknij_wraca_do_listy(): void
    {
        $admin = $this->admin();
        $payload = ['title' => 'Nowa', 'published_at' => now()->format('Y-m-d\TH:i'), 'is_published' => '1'];

        $this->actingAs($admin)->post(route('admin.newsy.store'), $payload + ['after' => 'stay']);
        $created = News::where('title', 'Nowa')->firstOrFail();
        $this->assertTrue(true);

        $this->actingAs($admin)->put(route('admin.newsy.update', $created), ['title' => 'Nowa 2'] + $payload + ['after' => 'stay'])
            ->assertRedirect(route('admin.newsy.edit', $created));
        $this->actingAs($admin)->put(route('admin.newsy.update', $created), ['title' => 'Nowa 3'] + $payload + ['after' => 'close'])
            ->assertRedirect(route('admin.newsy.index'));
        $this->actingAs($admin)->put(route('admin.newsy.update', $created), ['title' => 'Nowa 4'] + $payload)
            ->assertRedirect(route('admin.newsy.index'));
    }

    public function test_blad_walidacji_oznacza_zakladke_z_bledem(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)->from(route('admin.newsy.create'))->followingRedirects()
            ->post(route('admin.newsy.store'), ['title' => '', 'published_at' => 'nie-data'])
            ->getContent();

        $this->assertStringContainsString('(są błędy w tej zakładce)', $html);
        $this->assertSame(2, substr_count($html, '(są błędy w tej zakładce)'), 'Błąd w „Ogólne” (tytuł) i „Publikacja” (data).');
    }
}
