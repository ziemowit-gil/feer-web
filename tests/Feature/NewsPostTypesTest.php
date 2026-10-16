<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPostTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function news(array $attrs): News
    {
        return News::create($attrs + [
            'title' => 'Wpis', 'slug' => 'wpis-'.uniqid(), 'content' => '<p>Treść wpisu.</p>',
            'is_published' => true, 'published_at' => now()->subDay(),
        ]);
    }

    public function test_komunikat_znika_z_listy_po_dacie_waznosci_i_trafia_do_archiwum(): void
    {
        $active = $this->news(['title' => 'Biuro nieczynne w piątek', 'slug' => 'biuro', 'post_type' => 'announcement',
            'expires_at' => now()->addDays(2), 'type_data' => ['important' => true, 'cta_label' => 'Kontakt', 'cta_url' => '/kontakt']]);
        $expired = $this->news(['title' => 'Nabór zakończony', 'slug' => 'nabor', 'post_type' => 'announcement', 'expires_at' => now()->subHour()]);

        $this->assertTrue(News::published()->whereKey($active->id)->exists());
        $this->assertFalse(News::published()->whereKey($expired->id)->exists(), 'Po dacie ważności komunikat od razu znika z list.');

        $this->get(route('news.show', $active))->assertOk()
            ->assertSee('Ważny komunikat')->assertSee('Ważny do')->assertSee('href="/kontakt"', false);
        $this->get(route('news.show', $expired))->assertOk()
            ->assertSee('Komunikat nieaktualny')->assertSee('Był ważny do');

        $this->artisan('news:archiwizuj-komunikaty')->assertSuccessful();
        $this->assertTrue($expired->fresh()->is_archived);
        $this->assertFalse($active->fresh()->is_archived);
    }

    public function test_test_narzedzia_pokazuje_ocene_audyt_plusy_i_minusy(): void
    {
        $review = $this->news(['title' => 'Testujemy Canvę', 'slug' => 'canva', 'post_type' => 'review', 'type_data' => [
            'tool_name' => 'Canva', 'tool_url' => 'https://www.canva.com', 'score' => 3, 'compliance' => 'partial',
            'tested_with' => 'NVDA + Firefox', 'tested_at' => '2026-10-01', 'verdict' => 'Dobre do prostych grafik.',
            'pros' => ['Obsługa klawiaturą', ''], 'cons' => ['Brak alt w szablonach'], 'audit_summary' => 'Kontrast w porządku.',
        ]]);

        $this->get(route('news.show', $review))->assertOk()
            ->assertSee('Test narzędzia:')
            ->assertSee('href="https://www.canva.com"', false)
            ->assertSee('3 z 5')
            ->assertSee('Częściowo zgodne z WCAG 2.2 AA')
            ->assertSee('NVDA + Firefox')
            ->assertSee('Dobre do prostych grafik.')
            ->assertSee('Obsługa klawiaturą')
            ->assertSee('Brak alt w szablonach')
            ->assertSee('Podsumowanie audytu dostępności')
            ->assertSee('"@type":"Review"', false);
    }

    public function test_panel_zapisuje_typy_i_waliduje_wymagane_pola(): void
    {
        $this->useClassicNewsForm();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $base = ['title' => 'Komunikat', 'published_at' => now()->format('Y-m-d H:i'), 'is_published' => '1', 'content' => '<p>x</p>'];

        $this->actingAs($admin)->get(route('admin.newsy.create'))->assertOk()
            ->assertSee('name="post_type" value="announcement"', false)
            ->assertSee('name="type_data[score]"', false);

        $this->actingAs($admin)->post(route('admin.newsy.store'), $base + ['post_type' => 'announcement'])
            ->assertSessionHasErrors('expires_at');

        $this->actingAs($admin)->post(route('admin.newsy.store'), $base + ['post_type' => 'review', 'title' => 'Test'])
            ->assertSessionHasErrors(['type_data.tool_name', 'type_data.score']);

        $this->actingAs($admin)->post(route('admin.newsy.store'), $base + [
            'post_type' => 'announcement', 'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'type_data' => ['important' => '1', 'cta_label' => 'Zapisz się', 'cta_url' => '/zapisy', 'tool_name' => 'śmieć'],
        ])->assertSessionHasNoErrors();

        $n = News::where('title', 'Komunikat')->latest('id')->firstOrFail();
        $this->assertSame('announcement', $n->post_type);
        $this->assertNotNull($n->expires_at);
        $this->assertTrue($n->type_data['important']);
        $this->assertArrayNotHasKey('tool_name', $n->type_data, 'Pola innego typu nie są zapisywane.');
    }
}
