<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecruitmentListingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        Carbon::setTestNow('2026-10-11 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function make(string $type, string $slug, string $title, array $typeData = [], array $attrs = []): Page
    {
        return Page::create($attrs + [
            'title' => $title,
            'slug' => $slug,
            'type' => $type,
            'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    private function listingPage(string $shortcode, string $slug = 'lista'): Page
    {
        return Page::create([
            'title' => 'Lista',
            'slug' => $slug,
            'type' => 'standard',
            'is_published' => true,
            'content' => '<p>Wstęp.</p><p>['.$shortcode.']</p>',
        ]);
    }

    public function test_shortcode_is_detected_and_not_wrapped_in_paragraph(): void
    {
        $this->assertTrue(ShortcodeParser::has('<p>[nabory]</p>'));
        $this->assertTrue(ShortcodeParser::has('[zapytania-ofertowe]'));

        $html = ShortcodeParser::render('<p>[nabory]</p>');
        $this->assertStringNotContainsString('<p><section', $html);
        $this->assertStringContainsString('id="lista-naborow"', $html);
    }

    public function test_recruitment_listing_filters_sorts_and_shows_archive(): void
    {
        $this->make('recruitment', 'n-later', 'Nabór później', ['rec_ends_at' => '2026-11-30', 'lead' => 'Szukamy uczestników.', 'rec_places' => '20 osób']);
        $this->make('recruitment', 'n-sooner', 'Nabór wcześniej', ['rec_ends_at' => '2026-10-20']);
        $this->make('recruitment', 'n-continuous', 'Nabór ciągły bez dat');
        $this->make('recruitment', 'n-old', 'Nabór dawny', ['rec_ends_at' => '2026-01-31']);
        $this->make('recruitment', 'n-older', 'Nabór dawniejszy', ['rec_ends_at' => '2025-06-30']);
        $this->make('recruitment', 'n-draft', 'Nabór szkic', ['rec_ends_at' => '2026-10-25'], ['is_published' => false]);
        $this->make('recruitment', 'n-future', 'Nabór zaplanowana publikacja', [], ['publish_at' => '2026-12-01 00:00:00']);
        $this->listingPage('nabory');

        $response = $this->get('/lista')->assertOk();
        $response->assertSeeInOrder(['Nabór wcześniej', 'Nabór później', 'Nabór ciągły bez dat'])
            ->assertSee('Szukamy uczestników.')
            ->assertSee('do 30 listopada 2026')
            ->assertSee('Nabór trwa')
            ->assertDontSee('Nabór dawny')
            ->assertDontSee('Nabór szkic')
            ->assertDontSee('Nabór zaplanowana publikacja')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Aktualne (3)')
            ->assertSee('Zakończone (2)')
            ->assertSee('Wszystkie (5)');
        $this->assertStringNotContainsString('<p>[nabory]</p>', $response->getContent());

        $this->get('/lista?status=zakonczone')->assertOk()
            ->assertSeeInOrder(['Nabór dawny', 'Nabór dawniejszy'])
            ->assertSee('Nabór zakończony')
            ->assertDontSee('Nabór wcześniej');

        $this->get('/lista?status=wszystkie')->assertOk()
            ->assertSeeInOrder(['Nabór wcześniej', 'Nabór później', 'Nabór ciągły bez dat', 'Nabór dawny', 'Nabór dawniejszy']);

        // Nieznana wartość filtra = aktualne.
        $this->get('/lista?status=xyz')->assertOk()->assertSee('Nabór wcześniej')->assertDontSee('Nabór dawny');
    }

    public function test_tender_listing_filters_and_shows_case_number(): void
    {
        $this->make('tender', 'z-open', 'Zapytanie otwarte', ['tender_number' => 'ZO/7/2026', 'tender_deadline' => '2026-10-20T10:00', 'lead' => 'Szkolenia z dostępności.']);
        $this->make('tender', 'z-eval', 'Zapytanie w ocenie', ['tender_deadline' => '2026-10-01']);
        $this->make('tender', 'z-awarded', 'Zapytanie rozstrzygnięte', ['tender_deadline' => '2026-09-01', 'tender_result' => 'Wybrano ofertę firmy X.']);
        $this->make('tender', 'z-cancelled', 'Zapytanie unieważnione', ['tender_status' => 'cancelled', 'tender_deadline' => '2026-08-01']);
        $this->listingPage('zapytania-ofertowe');

        $this->get('/lista')->assertOk()
            ->assertSeeInOrder(['Zapytanie w ocenie', 'Zapytanie otwarte'])
            ->assertSee('ZO/7/2026')
            ->assertSee('20 października 2026, godz. 10:00')
            ->assertSee('Przyjmowanie ofert')
            ->assertSee('Szkolenia z dostępności.')
            ->assertDontSee('Zapytanie rozstrzygnięte');

        $this->get('/lista?status=zakonczone')->assertOk()
            ->assertSeeInOrder(['Zapytanie rozstrzygnięte', 'Zapytanie unieważnione'])
            ->assertDontSee('Zapytanie otwarte');
    }

    public function test_listing_shows_empty_state_and_paginates(): void
    {
        $this->listingPage('nabory');
        $this->get('/lista')->assertOk()->assertSee('Obecnie nie prowadzimy naborów.');

        for ($i = 1; $i <= 14; $i++) {
            $this->make('recruitment', 'n-'.$i, sprintf('Nabór numer %02d', $i), ['rec_ends_at' => sprintf('2026-11-%02d', $i)]);
        }

        $this->get('/lista')->assertOk()
            ->assertSee('Nabór numer 12')
            ->assertDontSee('Nabór numer 13')
            ->assertSee('Strona 1 z 2')
            ->assertSee('Następna strona');

        $this->get('/lista?strona=2')->assertOk()
            ->assertSee('Nabór numer 13')
            ->assertSee('Nabór numer 14')
            ->assertDontSee('Nabór numer 01')
            ->assertSee('Poprzednia strona');
    }

    public function test_listing_shows_only_current_site_pages(): void
    {
        $other = SiteSetting::create(['site_name' => 'Ośrodek', 'slug' => 'osrodek', 'parent_site_id' => SiteSetting::current()->id]);
        $this->make('recruitment', 'n-here', 'Nabór tutaj', ['rec_ends_at' => '2026-10-30']);
        $this->make('recruitment', 'n-there', 'Nabór w innej witrynie', ['rec_ends_at' => '2026-10-30'], ['site_id' => $other->id]);
        $this->listingPage('nabory');

        $this->get('/lista')->assertOk()->assertSee('Nabór tutaj')->assertDontSee('Nabór w innej witrynie');
    }

    public function test_home_shows_only_recruitments_and_tenders_ending_soon(): void
    {
        $this->make('recruitment', 'n-soon', 'Nabór kończy się wkrótce', ['rec_ends_at' => '2026-10-20', 'lead' => 'Ostatnie miejsca.']);
        $this->make('recruitment', 'n-far', 'Nabór kończy się za miesiąc', ['rec_ends_at' => '2026-11-20']);
        $this->make('recruitment', 'n-continuous', 'Nabór ciągły', []);
        $this->make('recruitment', 'n-closed', 'Nabór zakończony wczoraj', ['rec_ends_at' => '2026-10-10']);
        $this->make('recruitment', 'n-suspended', 'Nabór wstrzymany', ['rec_status' => 'suspended', 'rec_ends_at' => '2026-10-15']);
        $this->make('tender', 'z-soon', 'Zapytanie z bliskim terminem', ['tender_deadline' => '2026-10-14T10:00', 'tender_number' => 'ZO/9/2026']);
        $this->make('tender', 'z-far', 'Zapytanie z odległym terminem', ['tender_deadline' => '2026-10-30']);
        $this->listingPage('nabory', 'nabory');

        $this->get('/')->assertOk()
            ->assertSee('Ostatnie dni naboru')
            ->assertSee('Nabór kończy się wkrótce')
            ->assertSee('zostało 9 dni')
            ->assertSee('Zapytanie z bliskim terminem')
            ->assertSee('ZO/9/2026')
            ->assertSee('Zobacz wszystkie nabory')
            ->assertSee('/nabory', false)
            ->assertDontSee('Nabór kończy się za miesiąc')
            ->assertDontSee('Nabór ciągły')
            ->assertDontSee('Nabór zakończony wczoraj')
            ->assertDontSee('Nabór wstrzymany')
            ->assertDontSee('Zapytanie z odległym terminem')
            ->assertDontSee('Zobacz wszystkie zapytania ofertowe');
    }

    public function test_home_section_is_hidden_when_nothing_ends_soon(): void
    {
        $this->make('recruitment', 'n-far', 'Nabór kończy się za miesiąc', ['rec_ends_at' => '2026-11-20']);

        $this->get('/')->assertOk()
            ->assertDontSee('konczace-sie-nabory', false)
            ->assertDontSee('Nabór kończy się za miesiąc');
    }

    public function test_feer_template_home_includes_the_section(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->make('recruitment', 'n-soon', 'Nabór kończy się wkrótce', ['rec_ends_at' => '2026-10-13']);

        $this->get('/')->assertOk()->assertSee('Nabór kończy się wkrótce')->assertSee('zostało 2 dni');
    }
}
