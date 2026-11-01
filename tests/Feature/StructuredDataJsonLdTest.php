<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Event;
use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\AssertsJsonLd;
use Tests\TestCase;

/**
 * Dane strukturalne JSON-LD (schema.org) na stronach publicznych.
 *
 * Blade w Laravelu 13.20 ma dyrektywę `@context`. Kompilator przetwarza dyrektywy przed
 * wypisywaniem (`{!! !!}`), więc `'@context' => …` wewnątrz `{!! json_encode([…]) !!}` było
 * zamieniane na kod PHP: zamiast klucza `@context` w JSON-LD lądował napis
 * `<?php $__contextArgs = []; …`, a roboty wyszukiwarek nie widziały danych strukturalnych.
 * W widokach piszemy więc `'@@context'` (Blade zamienia `@@` na `@`).
 */
class StructuredDataJsonLdTest extends TestCase
{
    use AssertsJsonLd;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetSiteSettings();
    }

    private function forgetSiteSettings(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function useTemplate(string $template): void
    {
        SiteSetting::current()->update(['site_template' => $template]);
        $this->forgetSiteSettings();
    }

    /** @param array<string, array<string, mixed>> $blocks bloki z assertJsonLdTypes() */
    private function assertBlockValues(array $blocks, array $expected): void
    {
        foreach ($expected as $type => $values) {
            foreach ($values as $path => $value) {
                $this->assertSame($value, Arr::get($blocks[$type], $path), "{$type}: {$path}");
            }
        }
    }

    private function news(array $attrs = []): News
    {
        return News::create($attrs + [
            'title' => 'Nowa aktualność', 'slug' => 'nowa-aktualnosc', 'excerpt' => 'Krótko o wpisie.', 'content' => '<p>Treść wpisu.</p>',
            'is_published' => true, 'published_at' => now()->subDay(),
        ]);
    }

    /** @return array<string, list<string>> */
    public static function siteTemplates(): array
    {
        return ['default' => ['default'], 'feer' => ['feer'], 'test2' => ['test2'], 'test3' => ['test3']];
    }

    // ── Strony publiczne: każdy blok ld+json jest poprawnym JSON-em z @context ─────

    #[\PHPUnit\Framework\Attributes\DataProvider('siteTemplates')]
    public function test_strona_glowna_ma_dane_organizacji(string $template): void
    {
        $this->useTemplate($template);
        SiteSetting::current()->update(['site_name' => 'Fundacja Testowa', 'contact_email' => 'biuro@example.org']);
        $this->forgetSiteSettings();

        $blocks = $this->assertJsonLdTypes($this->get('/')->assertOk()->getContent(), ['Organization']);

        $this->assertBlockValues($blocks, ['Organization' => [
            '@context' => 'https://schema.org', 'name' => 'Fundacja Testowa', 'url' => url('/'), 'email' => 'biuro@example.org',
        ]]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('siteTemplates')]
    public function test_aktualnosc_ma_artykul_z_poprawnym_jsonld(string $template): void
    {
        $this->useTemplate($template);
        $news = $this->news();

        $blocks = $this->assertJsonLdTypes($this->get(route('news.show', $news))->assertOk()->getContent(), ['Organization', 'NewsArticle']);

        $this->assertBlockValues($blocks, ['NewsArticle' => [
            'headline' => 'Nowa aktualność', 'description' => 'Krótko o wpisie.', 'publisher.@type' => 'Organization',
        ]]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('siteTemplates')]
    public function test_test_narzedzia_ma_obok_artykulu_recenzje_z_poprawnym_jsonld(string $template): void
    {
        $this->useTemplate($template);
        $review = $this->news(['title' => 'Testujemy Canvę', 'slug' => 'canva', 'post_type' => 'review', 'type_data' => [
            'tool_name' => 'Canva', 'tool_url' => 'https://www.canva.com', 'score' => 3, 'verdict' => 'Dobre do prostych grafik.',
            'pros' => ['Obsługa klawiaturą'], 'cons' => ['Brak alt w szablonach'],
        ]]);

        $blocks = $this->assertJsonLdTypes($this->get(route('news.show', $review))->assertOk()->getContent(), ['Organization', 'NewsArticle', 'Review']);

        $this->assertBlockValues($blocks, ['Review' => [
            'itemReviewed.name' => 'Canva', 'reviewRating.ratingValue' => 3, 'positiveNotes.itemListElement.0.name' => 'Obsługa klawiaturą',
        ]]);
    }

    public function test_wydarzenie_stacjonarne_i_zdalne_ma_event_z_poprawnym_jsonld(): void
    {
        $base = [
            'title' => 'Szkolenie z dostępności (WCAG)', 'slug' => 'szkolenie-wcag', 'lead' => 'Praktyczne wprowadzenie do WCAG.', 'type' => 'szkolenie',
            'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'is_published' => true,
        ];
        $onsite = Event::create($base + ['mode' => 'stacjonarnie', 'location' => 'Nowy Sącz, ul. Barbackiego 28']);
        $remote = Event::create(array_merge($base, ['slug' => 'webinar-wcag', 'mode' => 'zdalnie', 'online_url' => 'https://meet.example.org/wcag']));

        $blocks = $this->assertJsonLdTypes($this->get(route('events.show', $onsite))->assertOk()->getContent(), ['Organization', 'Event']);
        $this->assertBlockValues($blocks, ['Event' => [
            'name' => 'Szkolenie z dostępności (WCAG)', 'location.@type' => 'Place', 'location.name' => 'Nowy Sącz, ul. Barbackiego 28',
        ]]);

        $blocks = $this->assertJsonLdTypes($this->get(route('events.show', $remote))->assertOk()->getContent(), ['Organization', 'Event']);
        $this->assertBlockValues($blocks, ['Event' => ['location.@type' => 'VirtualLocation', 'location.url' => 'https://meet.example.org/wcag']]);
    }

    public function test_szkolenie_z_modulu_szkolen_ma_course_z_terminami(): void
    {
        $course = Course::create(['title' => 'Czytnik ekranu od podstaw', 'slug' => 'czytnik', 'lead' => 'Nauka NVDA.', 'category' => 'tyflo', 'format' => 'online', 'price' => 120, 'status' => 'active']);
        $course->editions()->create(['starts_on' => now()->addWeek(), 'ends_on' => now()->addWeeks(2), 'capacity' => 5, 'enrollment_status' => 'open', 'location' => 'Kraków']);
        $course->editions()->create(['starts_on' => now()->addMonths(2), 'capacity' => 5, 'enrollment_status' => 'open']);

        $blocks = $this->assertJsonLdTypes($this->get('/zajecia/czytnik')->assertOk()->getContent(), ['Organization', 'Course']);

        $this->assertBlockValues($blocks, ['Course' => [
            'name' => 'Czytnik ekranu od podstaw', 'provider.@type' => 'Organization',
            'hasCourseInstance.0.@type' => 'CourseInstance', 'hasCourseInstance.0.location.name' => 'Kraków',
        ]]);
        $this->assertCount(2, $blocks['Course']['hasCourseInstance']);
    }

    /**
     * Strony typowane (page/partials/typed/*) i FAQ (partials/faq): tytuł strony ma trafić do danych strukturalnych.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: array<string, array<string, mixed>>}>
     */
    public static function typedPages(): array
    {
        return [
            'oferta' => ['service', [
                'type_data' => ['lead' => 'Sprawdzamy stronę ręcznie i narzędziami.', 'audience' => [['text' => 'Urzędy'], ['text' => 'Organizacje pozarządowe']]],
            ], ['Service' => ['name' => 'Strona service', 'audience.0.audienceType' => 'Urzędy']]],
            'poradnik' => ['guide', [
                'type_data' => [
                    'lead' => 'Jak zrobić dostępny PDF.', 'requirements' => [['text' => 'Microsoft Word 2016']],
                    'steps' => [['title' => 'Użyj stylów nagłówków', 'text' => 'Nagłówek 1, 2, 3.'], ['title' => 'Zapisz jako PDF', 'text' => 'Zaznacz znaczniki struktury.']],
                ],
            ], ['HowTo' => ['name' => 'Strona guide', 'step.0.@type' => 'HowToStep', 'step.1.name' => 'Zapisz jako PDF', 'supply.0.name' => 'Microsoft Word 2016']]],
            'slownik' => ['glossary', [
                'type_data' => ['lead' => 'Pojęcia prostym językiem.', 'terms' => [['term' => 'Czytnik ekranu', 'definition' => 'Program odczytujący ekran.'], ['term' => 'WCAG', 'definition' => 'Wytyczne dostępności.']]],
            ], ['DefinedTermSet' => ['name' => 'Strona glossary', 'hasDefinedTerm.0.@type' => 'DefinedTerm', 'hasDefinedTerm.0.name' => 'Czytnik ekranu']]],
            'studium przypadku' => ['case_study', [
                'type_data' => ['lead' => 'Jak gmina poprawiła zgodność z WCAG.', 'client' => 'Gmina', 'sector' => 'Samorząd', 'period' => '2026'],
            ], ['Article' => ['headline' => 'Strona case_study', 'about.name' => 'Gmina']]],
            'szkolenie z terminami i FAQ' => ['training_offer', [
                'type_data' => [
                    'lead' => 'Nauczysz się tworzyć dostępne dokumenty.', 'mode' => 'online',
                    'editions' => [['date' => '2027-03-01', 'place' => 'Kraków']],
                    'faq' => [['question' => 'Czy dostanę certyfikat?', 'answer' => 'Tak, każdy uczestnik.']],
                ],
            ], [
                'Course' => ['name' => 'Strona training_offer', 'hasCourseInstance.0.@type' => 'CourseInstance', 'hasCourseInstance.0.startDate' => '2027-03-01'],
                'FAQPage' => ['mainEntity.0.@type' => 'Question', 'mainEntity.0.name' => 'Czy dostanę certyfikat?', 'mainEntity.0.acceptedAnswer.text' => 'Tak, każdy uczestnik.'],
            ]],
            'FAQ' => ['faq', [
                'faq_items' => [['question' => 'Czy to działa?', 'answer' => '<p>Tak, <strong>działa</strong>.</p>']],
            ], ['FAQPage' => ['mainEntity.0.name' => 'Czy to działa?', 'mainEntity.0.acceptedAnswer.text' => 'Tak, działa.']]],
        ];
    }

    /** @param array<string, mixed> $attrs @param array<string, array<string, mixed>> $expected */
    #[\PHPUnit\Framework\Attributes\DataProvider('typedPages')]
    public function test_strona_typowana_ma_poprawne_jsonld(string $type, array $attrs, array $expected): void
    {
        Page::create($attrs + ['title' => 'Strona '.$type, 'slug' => 'strona-'.$type, 'type' => $type, 'is_published' => true]);

        $blocks = $this->assertJsonLdTypes($this->get('/strona-'.$type)->assertOk()->getContent(), ['Organization', ...array_keys($expected)]);

        $this->assertBlockValues($blocks, $expected);
    }

    // ── Statycznie: żaden widok nie zostawia gołego @context do skompilowania ──────

    public function test_dyrektywa_context_wewnatrz_echo_psuje_klucz_json_ld(): void
    {
        if (! method_exists(BladeCompiler::class, 'compileContext')) {
            $this->markTestSkipped('Ta wersja Laravela nie ma dyrektywy Blade @context — nie ma czego pilnować.');
        }

        // Dokumentuje przyczynę błędu i sprawdza, czy test niżej nadal rozpoznaje skutek (kod dyrektywy w skompilowanym widoku).
        $compiled = $this->app->make('blade.compiler')->compileString("{!! json_encode(['@context' => 'https://schema.org']) !!}");

        $this->assertStringContainsString('$__contextArgs = [];', $compiled);
    }

    public function test_podwojny_malpa_daje_w_json_ld_literalne_context(): void
    {
        $html = Blade::render("<script type=\"application/ld+json\">{!! json_encode(['@@context' => 'https://schema.org', '@type' => 'Organization'], JSON_UNESCAPED_SLASHES) !!}</script>");

        $this->assertSame('<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization"}</script>', $html);
    }

    public function test_argument_dyrektywy_json_moze_zawierac_context(): void
    {
        $template = "<script type=\"application/ld+json\">@json(['@context' => 'https://schema.org', '@type' => 'Organization'])</script>";

        $this->assertStringNotContainsString('$__contextArgs', $this->app->make('blade.compiler')->compileString($template));
        $this->assertSame('<script type="application/ld+json">{"@context":"https:\/\/schema.org","@type":"Organization"}</script>', Blade::render($template));
    }

    public function test_w_widokach_nie_ma_nieoslonietego_context(): void
    {
        $dirs = array_merge([resource_path('views')], glob(base_path('modules/*/resources/views'), GLOB_ONLYDIR) ?: []);
        $compiler = $this->app->make('blade.compiler');

        $broken = [];
        foreach (Finder::create()->files()->name('*.blade.php')->in($dirs) as $file) {
            $source = $file->getContents();
            if (! str_contains($source, '@context')) {
                continue;
            }

            // Zapis @@context i argument w @json(…) kompilator zostawia w spokoju; goły @context staje się dyrektywą bez argumentów.
            if (! str_contains($compiler->compileString($source), '$__contextArgs = [];')) {
                continue;
            }

            preg_match_all('/(?<!@)@context\b/', $source, $found, PREG_OFFSET_CAPTURE);
            foreach ($found[0] as [, $offset]) {
                $broken[] = str_replace(base_path().'/', '', $file->getPathname()).':'.(substr_count($source, "\n", 0, $offset) + 1);
            }
        }

        $this->assertSame([], $broken, "Goły @context jest kompilowany jako dyrektywa Blade — zapisz '@@context':\n".implode("\n", $broken));
    }
}
