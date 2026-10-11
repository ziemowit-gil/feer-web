<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel w płaskim stylu: statusy to zwykły tekst z ikoną (bez kolorowych pigułek),
 * a tekst w kolorze marki używa ciemnego odcienia (text-brand-dark) z kontrastem ≥ 4,5:1.
 */
class FlatAdminStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public static function screens(): array
    {
        return [
            'pulpit' => ['admin.dashboard'],
            'strony' => ['admin.podstrony.index'],
            'aktualności' => ['admin.newsy.index'],
            'ustawienia' => ['admin.ustawienia.edit'],
            'użytkownicy' => ['admin.uzytkownicy.index'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('screens')]
    public function test_ekran_panelu_nie_ma_kolorowych_pigulek_ani_jasnego_text_brand(string $route): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_EDITOR]);
        News::create(['title' => 'Opublikowany news', 'slug' => 'opublikowany', 'content' => '<p>x</p>', 'published_at' => now()->subDay(), 'is_published' => true]);
        News::create(['title' => 'Szkic newsa', 'slug' => 'szkic', 'content' => '<p>x</p>', 'is_published' => false]);
        Page::create(['title' => 'Strona opublikowana', 'slug' => 'opublikowana', 'type' => 'standard', 'is_published' => true]);
        Page::create(['title' => 'Strona szkic', 'slug' => 'strona-szkic', 'type' => 'standard', 'is_published' => false]);

        $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<main\b[^>]*id="main"/', $html);
        preg_match('/<main\b[^>]*id="main"[^>]*>(.*)<\/main>/s', $html, $m);
        $main = $m[1] ?? '';
        $this->assertNotSame('', $main, 'Nie znaleziono treści <main>.');
        // Skrypty pomijamy: edytor buduje w nich znaczniki wstawiane do treści STRONY PUBLICZNEJ
        // (np. ramka załącznika), które mają wygląd serwisu, nie panelu.
        $main = preg_replace('/<script\b.*?<\/script>/s', '', $main);

        // Kolorowe pigułki statusu: rounded-full + jasne tło z palety w jednym atrybucie class.
        preg_match_all('/class="([^"]*)"/', $main, $classes);
        $pills = array_filter($classes[1], fn (string $c) => preg_match('/(?<![\w:-])rounded-full(?![\w-])/', $c)
            && preg_match('/(?<![\w:\/-])bg-(green|red|amber|yellow|blue|purple)-100(?![\w\/-])/', $c));
        $this->assertSame([], array_values($pills), 'Kolorowe pigułki statusu w panelu: '.implode(' | ', $pills));

        // Jasny kolor marki jako kolor tekstu (za niski kontrast na bieli) — tylko text-brand-dark.
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])text-brand(?![-\w])/', $main);
    }
}
