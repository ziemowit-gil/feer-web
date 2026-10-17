<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Motyw panelu w duchu Joomla: nagłówek, pasek narzędzi, menu i kafle „Panel sterowania” na pulpicie. */
class AdminJoomlaThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_pulpit_admina_ma_motyw_naglowek_i_kafle(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('<body data-scheme="krem" class="admin-joomla', $html);
        $this->assertStringContainsString('--j-cream: #f7f7e8', $html);
        $this->assertMatchesRegularExpression('~<h1 class="j-title[^"]*">\s*<span class="j-title-icon" aria-hidden="true"><i class="fa-solid fa-[a-z-]+"></i></span>~u', $html);
        $this->assertStringContainsString('Zobacz stronę<span class="sr-only"> (otwiera się w nowej karcie)</span>', $html);
        $this->assertStringContainsString('<button type="submit" class="j-link"><i class="fa-solid fa-right-from-bracket text-xs" aria-hidden="true"></i> Wyloguj</button>', $html);
        $this->assertStringContainsString('<h2 id="dash-tiles"', $html);
        $this->assertStringContainsString('Panel sterowania', $html);
        $this->assertStringContainsString('href="'.route('admin.uzytkownicy.index').'" class="j-tile w-full"', $html);
        $this->assertStringContainsString('href="'.route('admin.kopie.index').'" class="j-tile w-full"', $html);
    }

    public function test_redaktor_nie_widzi_kafli_administracyjnych(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $html = $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('href="'.route('admin.uzytkownicy.index').'" class="j-tile', $html);
        $this->assertStringNotContainsString('href="'.route('admin.ustawienia.edit').'" class="j-tile', $html);
    }

    public function test_inne_strony_panelu_tez_maja_motyw(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<body data-scheme="krem" class="admin-joomla', $html);
        $this->assertStringContainsString('class="j-toolbar', $html);
        $this->assertStringContainsString('<i class="fa-solid fa-file-lines"></i></span>', $html); // ikona sekcji „Strony” przy tytule
    }
}
