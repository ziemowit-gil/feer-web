<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\News;
use App\Models\Poll;
use App\Models\Project;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Płaski styl (jak strona w układzie portalu) na stronach działań i na stronie głównej:
 * w treści <main> nie ma plakietek/pigułek, ozdobnych ramek i linii, gradientów, przezroczystości
 * na tekście ani tekstu w jasnym kolorze marki (text-brand) — a treść i funkcje zostają.
 */
class FlatProjectsHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettings();
    }

    private function resetSettings(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
    }

    private function useTemplate(string $template, array $extra = []): void
    {
        SiteSetting::current()->update(array_merge(['site_template' => $template], $extra));
        $this->resetSettings();
    }

    /** Treść <main> strony. */
    private function main(string $html): string
    {
        $this->assertSame(1, preg_match('~<main id="main-content"[^>]*>(.*)</main>~s', $html, $m), 'brak <main>');

        return $m[1];
    }

    /** Sprawdza klasy (class="…") w treści: zakazane wzorce stylu „nie-płaskiego". */
    private function assertFlat(string $html, string $label): void
    {
        $main = $this->main($html);
        // Wspólne elementy spoza tych widoków (ścieżka nawigacyjna serwisu, wspólna siatka kafli _tiles-grid) — poza zakresem.
        $main = preg_replace('~(<div class="border-b border-gray-200 bg-gray-50">\s*)?<nav aria-label="Ścieżka nawigacyjna".*?</nav>(\s*</div>)?~s', '', $main);
        $main = preg_replace('~<nav class="not-prose"[^>]*>.*?</nav>~s', '', $main);
        preg_match_all('~\sclass="([^"]*)"~', $main, $all);
        $tokens = collect($all[1])->flatMap(fn ($c) => preg_split('/\s+/', trim($c)))->filter()->unique()->values();

        $bad = $tokens->filter(function (string $t) {
            $base = preg_replace('/^([a-z0-9-]+:)+/', '', $t); // bez wariantów (hover:, lg:, group-hover: …)
            $variant = $t !== $base;

            return preg_match('/^text-brand$/', $base)                                   // jasny kolor marki jako tekst
                || $base === 'rounded-full'                                              // pigułki / kółka
                || preg_match('/^(bg-gradient|bg-linear|bg-radial|from-|via-)/', $base)  // gradienty
                || preg_match('/^(border|divide)(-|$)/', $base) && $base !== 'border-collapse' // ramki i linie
                || (! $variant && preg_match('/^ring(-\d|$)/', $base))                     // obwódki (poza fokusem)
                || preg_match('/^shadow(-|$)/', $base)                                   // cienie
                || preg_match('~^(text|bg)-[a-z-]+/\d+$~', $base)                        // przezroczystość koloru
                || (preg_match('/^opacity-\d+$/', $base) && ! in_array($base, ['opacity-0', 'opacity-100'], true)); // (0/100 — mechanika slajdera)
        });

        $this->assertSame([], $bad->values()->all(), $label.': klasy niezgodne z płaskim stylem');
        $this->assertStringNotContainsString('linear-gradient(', $main, $label.': gradient w stylu inline');
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia', 'order' => 0]);
    }

    public function test_strona_dzialania_z_typowymi_danymi_jest_plaska_i_ma_wszystkie_sekcje(): void
    {
        SiteSetting::current()->update(['projects_stages_enabled' => true, 'projects_team_funding_enabled' => true, 'show_coordinators' => true]);
        $this->resetSettings();

        Project::create([
            'category_id' => $this->category()->id, 'title' => 'Warsztaty WCAG', 'slug' => 'warsztaty-wcag', 'is_published' => true, 'show_on_home' => true,
            'excerpt' => 'Praktyczne warsztaty z dostępności.', 'for_whom' => 'Pracownicy urzędów', 'since' => '2024',
            'content' => '<p>Opis warsztatów.</p><p><strong>Ważne:</strong> liczba miejsc ograniczona.</p>', 'why' => 'Bo dostępność to prawo.',
            'outcomes' => '<p>Przeszkoliliśmy 300 osób.</p>',
            'status' => 'active', 'starts_on' => '2026-01-10', 'ends_on' => '2026-12-20',
            'is_paid' => true, 'pricing' => [['item' => 'Szkolenie 1-dniowe', 'price' => '1200 zł', 'note' => 'grupa do 15 osób']],
            'custom_sections' => [['title' => 'Wyróżniona sekcja', 'content' => '<p>Treść wyróżniona</p>', 'featured' => true], ['title' => 'Zwykła sekcja', 'content' => '<p>Treść zwykła</p>']],
            'quick_facts' => ['duration' => '2 dni', 'place' => 'Nowy Sącz', 'mode' => 'onsite', 'seats' => '20'],
            'easy_summary' => 'Uczymy, jak robić dostępne strony.',
            'main_cta' => ['label' => 'Zapisz się', 'url' => '/zapisy'],
            'terms' => [['label' => 'Wiek', 'text' => 'Osoby pełnoletnie']],
            'metrics' => [['value' => '300', 'label' => 'uczestników']],
            'testimonials' => [['text' => 'Świetne zajęcia', 'author' => 'Anna']],
            'faq' => [['q' => 'Czy dostanę certyfikat?', 'a' => 'Tak.']],
            'stages' => [['title' => 'Rekrutacja', 'state' => 'done'], ['title' => 'Zajęcia', 'state' => 'current']],
            'team' => [['name' => 'Jan Kowalski', 'role' => 'Trener']],
            'funding' => ['sources' => [['name' => 'Fundusz XYZ', 'url' => 'https://example.org']], 'budget' => '50 000 zł', 'budget_public' => true],
            'funding_notice' => 'Projekt dofinansowany ze środków XYZ.',
            'coordinator_name' => 'Ewa Nowak', 'coordinator_email' => 'ewa@example.org', 'coordinator_phone' => '600 000 000', 'show_coordinator' => true,
        ]);

        foreach (['default', 'feer'] as $template) {
            $this->useTemplate($template);
            $html = $this->get('/dzialania/warsztaty-wcag')->assertOk()->getContent();

            foreach (['Warsztaty WCAG', 'Praktyczne warsztaty z dostępności.', 'Status:', 'W trakcie', 'Opis działania', 'Opis warsztatów.', 'Dlaczego to robimy',
                'Prostym językiem', 'W skrócie', 'Kto może wziąć udział', 'Efekty w liczbach', 'Co mówią uczestnicy', 'Pytania i odpowiedzi', 'Etapy działania',
                'Zespół działania', 'Jan Kowalski', 'Wyróżniona sekcja', 'Zwykła sekcja', 'Co udało się osiągnąć', 'Cennik', '1200 zł', 'Zapisz się',
                'Masz pytania? Chętnie odpowiemy', 'ewa@example.org', 'Finansowanie', 'Fundusz XYZ', '50 000 zł', 'Projekt dofinansowany ze środków XYZ.', 'Pracownicy urzędów'] as $text) {
                $this->assertStringContainsString($text, $html, "[$template] brak: $text");
            }
            $this->assertStringContainsString('class="proj-callout"', $html);
            $this->assertStringContainsString('proj-aside', $html);
            $this->assertStringNotContainsString('proj-status', $html, 'status bez plakietki');
            $this->assertFlat($html, "działanie [$template]");
        }
    }

    public function test_listy_dzialan_sa_plaskie(): void
    {
        $category = $this->category();
        Project::create(['category_id' => $category->id, 'title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'excerpt' => 'Pomagamy', 'is_published' => true, 'show_on_home' => true, 'accent_color' => '#e6006f']);
        Project::create(['category_id' => $category->id, 'title' => 'Doradztwo', 'slug' => 'doradztwo', 'excerpt' => 'Radzimy', 'is_published' => true, 'show_on_home' => true, 'is_paid' => true]);
        Project::create(['category_id' => $category->id, 'title' => 'Stary projekt', 'slug' => 'stary', 'is_published' => true, 'is_completed' => true, 'completed_at' => now()->subYear()]);

        foreach (['default', 'feer'] as $template) {
            $this->useTemplate($template);
            foreach (['/dzialania', '/dzialania/archiwum'] as $url) {
                $this->assertFlat($this->get($url)->assertOk()->getContent(), "$url [$template]");
            }
            $html = $this->get('/dzialania')->assertOk()->assertSee('Wsparcie IT')->assertSee('Doradztwo')->getContent();
            $this->assertStringContainsString('Odpłatne', $html);
            $this->assertStringContainsString('Bezpłatne', $html);
            $this->assertStringNotContainsString('pf-badge', $html);
            $this->assertStringNotContainsString('border-left: 4px solid', $html);

            $this->assertFlat($this->get(route('categories.show', $category))->assertOk()->assertSee('Wsparcie IT')->getContent(), "kategoria [$template]");
            $this->get('/dzialania/archiwum')->assertOk()->assertSee('Stary projekt');
        }

        // Układ kafelkowy (Ustawienia → Treści) — kolorowe kafle bez przezroczystości i ramek.
        $this->useTemplate('default', ['projects_layout' => 'tiles']);
        $this->assertFlat($this->get('/dzialania')->assertOk()->assertSee('Wsparcie IT')->getContent(), 'kafelki');
    }

    public function test_strona_glowna_domyslna_i_feer_sa_plaskie_z_zachowaniem_sekcji(): void
    {
        $category = $this->category();
        Project::create(['category_id' => $category->id, 'title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'excerpt' => 'Pomagamy', 'is_published' => true, 'show_on_home' => true]);
        News::create(['title' => 'Ważna wiadomość', 'slug' => 'wazna', 'content' => 'x', 'excerpt' => 'Zajawka wiadomości', 'is_published' => true, 'published_at' => now()->subDay()]);
        QuickAction::create(['label' => 'Zgłoś barierę', 'url' => '/zgloszenia', 'icon' => 'fa-solid fa-flag', 'order' => 1]);
        \App\Models\HeroSlide::create(['title' => 'Zwykły slajd', 'text' => 'Opis slajdu', 'order' => 1]);
        $poll = Poll::create(['question' => 'Co dalej?', 'is_active' => true]);
        $poll->options()->create(['label' => 'Opcja A']);
        Event::create([
            'title' => 'Szkolenie WCAG', 'slug' => 'szkolenie-wcag', 'lead' => 'Wprowadzenie do WCAG.', 'type' => 'szkolenie', 'mode' => 'stacjonarnie',
            'location' => 'Nowy Sącz', 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'is_published' => true, 'is_featured' => true,
        ]);

        $this->useTemplate('default');
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('data-hero-toggle', $html); // przycisk wstrzymania slajdera
        $this->assertStringContainsString('aria-roledescription="karuzela"', $html);
        foreach (['Zwykły slajd', 'Aktualności', 'Ważna wiadomość', 'Na skróty', 'Zgłoś barierę', 'Co dalej?', 'Głosuj', 'Skontaktuj się z nami', 'Napisz do nas'] as $text) {
            $this->assertStringContainsString($text, $html, "[default] brak: $text");
        }
        $this->assertFlat($html, 'strona główna [default]');

        $this->useTemplate('feer');
        $html = $this->get('/')->assertOk()->getContent();
        foreach (['Na skróty', 'Zgłoś barierę', 'Ankieta', 'Głosuj', 'Aktualności', 'Ważna wiadomość', 'Nasze działania', 'Wsparcie IT'] as $text) {
            $this->assertStringContainsString($text, $html, "[feer] brak: $text");
        }
        // Kolejność sekcji szablonu FEER bez zmian.
        $order = array_map(fn ($id) => strpos($html, 'id="'.$id.'"'), ['feer-shortcuts-heading', 'ngo-news-heading', 'ngo-projects-heading', 'ngo-cta-heading']);
        $this->assertNotContains(false, $order);
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order, 'kolejność sekcji');
        // Jeden h1 (ukryty) na stronie głównej FEER.
        $this->assertSame(1, preg_match_all('~<h1[\s>]~', $this->main($html)));
        $this->assertFlat($html, 'strona główna [feer]');
    }
}
