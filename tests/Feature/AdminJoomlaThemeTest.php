<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Motyw panelu w duchu Joomla: nagłówek, pasek narzędzi, menu i uproszczony pulpit (Na dziś, Utwórz, listy). */
class AdminJoomlaThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_pulpit_admina_ma_motyw_naglowek_i_uproszczony_uklad(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('<body data-scheme="krem" class="admin-joomla', $html);
        $this->assertStringContainsString('--j-cream: #f7f7e8', $html);
        $this->assertMatchesRegularExpression('~<h1 class="j-title[^"]*">\s*<span class="j-title-icon" aria-hidden="true"><i class="fa-solid fa-[a-z-]+"></i></span>~u', $html);
        $this->assertStringContainsString('Zobacz stronę<span class="sr-only"> (otwiera się w nowej karcie)</span>', $html);
        $this->assertStringContainsString('<button type="submit" class="j-link"><i class="fa-solid fa-right-from-bracket text-xs" aria-hidden="true"></i> Wyloguj</button>', $html);
        // Pulpit jest przeglądem: sprawy „Na dziś” i lista „Utwórz”; kafle „Panel sterowania” duplikowały menu boczne.
        $this->assertStringContainsString('<h2 id="dash-attention"', $html);
        $this->assertStringContainsString('aria-controls="dash-create-menu"', $html);
        $this->assertStringNotContainsString('Panel sterowania', $html);
        $this->assertStringNotContainsString('class="j-tile', $html);
    }

    public function test_redaktor_ma_skroty_utworz_tylko_ze_swojego_zakresu(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $html = $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('admin.newsy.create').'"', $html);
        $this->assertStringNotContainsString('class="j-tile', $html);
        $this->assertStringNotContainsString('Panel sterowania', $html);
    }

    public function test_inne_strony_panelu_tez_maja_motyw(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<body data-scheme="krem" class="admin-joomla', $html);
        $this->assertStringNotContainsString('class="j-toolbar', $html); // ogólny pasek „Szybko” usunięty
        $this->assertStringContainsString('<i class="fa-solid fa-file-lines"></i></span>', $html); // ikona sekcji „Strony” przy tytule
    }
}
