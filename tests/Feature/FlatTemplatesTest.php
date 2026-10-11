<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Płaski styl portalu w pozostałych szablonach stron (wide, hero, landing, portal, minimal)
 * oraz na liście i w archiwum aktualności: bez text-brand na białym, „pigułek" (rounded-full),
 * dekoracyjnych ramek i gradientów — przy zachowanych funkcjach (edycja w miejscu, spis treści,
 * menu działu, kontakt, kategorie z aria-current, wyszukiwarka, paginacja).
 */
class FlatTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function page(array $attributes): Page
    {
        return Page::create($attributes + ['type' => 'standard', 'is_published' => true, 'content' => '<p>Treść strony</p>']);
    }

    /** Fragment HTML od znacznika szablonu do końca <main> (bez okruszków i nagłówka serwisu). */
    private function scoped(string $html, string $marker): string
    {
        $start = strpos($html, $marker);
        $this->assertNotFalse($start, "Brak znacznika {$marker}.");
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end ?: strlen($html)) - $start);
    }

    private function assertFlat(string $fragment, string $context): void
    {
        // Pola formularzy zachowują widoczne obramowanie — pomijamy je przy sprawdzaniu ramek.
        $withoutInputs = preg_replace('/<(input|select|textarea)\b[^>]*>/i', '', $fragment);
        preg_match_all('/class="([^"]*)"/', $withoutInputs, $m);
        $classes = implode(' ', $m[1]);

        $this->assertDoesNotMatchRegularExpression('/(?<![-\w])text-brand(?![-\w])/', $classes, "{$context}: text-brand na białym tle.");
        $this->assertDoesNotMatchRegularExpression('/(?<![-\w])rounded-full(?![-\w])/', $classes, "{$context}: pigułki rounded-full.");
        $this->assertDoesNotMatchRegularExpression('/(?<![-\w])(border|divide)-(?!0\b)[a-z0-9]/', $classes, "{$context}: dekoracyjne ramki/linie.");
        $this->assertDoesNotMatchRegularExpression('/(?<![-\w])(bg-gradient|bg-linear|from-[a-z]|via-[a-z])/', $classes, "{$context}: gradienty.");
        $this->assertDoesNotMatchRegularExpression('/(?<![-\w])(opacity-\d+|text-white\/\d+|bg-white\/\d+)/', $classes, "{$context}: przezroczystość na treści.");
    }

    public static function templates(): array
    {
        return [['wide'], ['hero'], ['landing'], ['portal'], ['minimal']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('templates')]
    public function test_szablon_strony_jest_plaski_i_zachowuje_funkcje(string $template): void
    {
        SiteSetting::current()->update(['contact_email' => 'biuro@example.org', 'contact_phone' => '12 345 67 89', 'contact_address' => 'ul. Testowa 1']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $parent = $this->page(['title' => 'Dział testowy', 'slug' => 'dzial-'.$template]);
        $this->page(['title' => 'Siostra', 'slug' => 'siostra-'.$template, 'parent_id' => $parent->id]);
        $this->page([
            'title' => 'Strona '.$template, 'slug' => 'strona-'.$template, 'parent_id' => $parent->id,
            'page_template' => $template, 'meta_description' => 'Opis strony testowej',
            'content' => '<h2>Pierwsza sekcja</h2><p>Akapit treści szablonu.</p><h2>Druga sekcja</h2><p>Dalej.</p>',
        ]);

        $html = $this->get('/strona-'.$template)->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<h1'), 'Dokładnie jeden nagłówek h1.');
        $this->assertStringContainsString('Akapit treści szablonu.', $html);
        $this->assertFlat($this->scoped($html, 'data-page-template="'.$template.'"'), $template);

        if (in_array($template, ['hero', 'landing', 'portal'], true)) {
            $this->assertStringContainsString('bg-brand-dark text-white', $html, 'Pas tytułowy jednolity brand-dark.');
        }
        if ($template === 'portal') {
            $this->assertStringContainsString('aria-labelledby="portal-contact-heading"', $html);
            $this->assertStringContainsString('mailto:biuro@example.org', $html);
            $this->assertStringContainsString('Siostra', $html, 'Menu działu w kolumnie bocznej.');
            $this->assertStringContainsString('Pierwsza sekcja', $html);
            $this->assertStringNotContainsString('<main class=', $this->scoped($html, 'data-page-template="portal"'), 'Bez zagnieżdżonego <main>.');
        }
        if ($template === 'wide') {
            $this->assertStringContainsString('Zaktualizowano <time', $html);
        }

        // Edycja w miejscu dla redakcji.
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get('/strona-'.$template)->assertOk()
            ->assertSee('data-inline-field="content"', false)
            ->assertSee('data-inline-field="title"', false);
    }

    public function test_szablony_landing_portal_minimal_pokazuja_komunikat_w_przygotowaniu(): void
    {
        foreach (['landing', 'portal', 'minimal'] as $template) {
            $this->page(['title' => 'WIP '.$template, 'slug' => 'wip-'.$template, 'page_template' => $template, 'wip_mode' => 'notice', 'wip_message' => 'Trwają prace '.$template]);
            $this->get('/wip-'.$template)->assertOk()->assertSee('Trwają prace '.$template)->assertSee('Informacja o trwających pracach');
        }
    }

    public function test_meta_description_nie_zawiera_shortcodow(): void
    {
        $this->page(['title' => 'Nabory', 'slug' => 'nabory-meta', 'content' => '<p>[nabory]</p><p>Lista otwartych naborów fundacji.</p>']);
        $this->page(['title' => 'Szeroka', 'slug' => 'szeroka-meta', 'page_template' => 'wide', 'content' => '<h2>Wstęp</h2><p>[formularz:kontakt] Napisz do nas.</p>']);

        $html = $this->get('/nabory-meta')->assertOk()->getContent();
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        $this->assertSame('Lista otwartych naborów fundacji.', html_entity_decode($m[1] ?? ''));

        $html = $this->get('/szeroka-meta')->assertOk()->getContent();
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        $this->assertSame('Wstęp Napisz do nas.', html_entity_decode($m[1] ?? ''));
    }

    private function news(string $title, ?NewsCategory $category = null, int $daysAgo = 1, array $extra = []): News
    {
        return News::create($extra + [
            'title' => $title, 'slug' => Str::slug($title), 'excerpt' => 'Zajawka: '.$title, 'content' => '<p>Treść</p>',
            'is_published' => true, 'published_at' => now()->subDays($daysAgo), 'news_category_id' => $category?->id,
        ]);
    }

    public static function siteTemplates(): array
    {
        return [['default'], ['feer']];
    }

    /** Lista aktualności (poprzedni układ: pasek kategorii, wyróżniony wpis, karty) — kolejność, filtr kategorii i stronicowanie. */
    #[\PHPUnit\Framework\Attributes\DataProvider('siteTemplates')]
    public function test_lista_aktualnosci_wyrozniony_wpis_filtr_i_strony(string $siteTemplate): void
    {
        SiteSetting::current()->update(['site_template' => $siteTemplate]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $edu = NewsCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja']);
        $this->news('Najnowszy wpis', $edu, 1);
        foreach (range(2, 12) as $i) {
            $this->news('Wpis numer '.$i, $i % 2 ? $edu : null, $i);
        }

        $html = $this->get('/aktualnosci')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Wpis numer 2'), strpos($html, 'Najnowszy wpis'));
        $this->assertStringContainsString('kategoria=edukacja', $html);

        $cat = $this->get('/aktualnosci?kategoria=edukacja')->assertOk()->getContent();
        $this->assertStringNotContainsString('Wpis numer 2<', $cat);

        $this->get('/aktualnosci?page=2')->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('siteTemplates')]
    public function test_archiwum_aktualnosci_jest_plaskie(string $siteTemplate): void
    {
        SiteSetting::current()->update(['site_template' => $siteTemplate]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->news('Stary wpis', null, 700, ['is_archived' => true]);

        $html = $this->get('/archiwum?tab=archiwalne')->assertOk()->assertSee('Stary wpis')->getContent();
        $scoped = $this->scoped($html, 'data-news-archive');
        $this->assertFlat($scoped, 'archiwum/'.$siteTemplate);
        $this->assertStringContainsString('aria-label="Zakres archiwum"', $scoped);
        $this->assertMatchesRegularExpression('/tab=archiwalne" aria-current="page"/', $scoped);
        $this->assertStringContainsString('Archiwalne', $scoped);
    }
}
