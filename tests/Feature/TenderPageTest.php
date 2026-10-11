<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenderPageTest extends TestCase
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

    private function tender(array $typeData = [], string $slug = 'zapytanie'): Page
    {
        return Page::create([
            'title' => 'Zapytanie ofertowe nr 3/2026 — usługa szkoleniowa',
            'slug' => $slug,
            'type' => 'tender',
            'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    public function test_status_follows_deadline_and_result(): void
    {
        $this->assertSame('open', $this->tender(['tender_deadline' => '2026-10-20T10:00'], 'a')->tenderStatus());
        // Sama data terminu obowiązuje do końca dnia.
        $this->assertSame('open', $this->tender(['tender_deadline' => '2026-10-11'], 'b')->tenderStatus());
        $this->assertSame('evaluation', $this->tender(['tender_deadline' => '2026-10-11T10:00'], 'c')->tenderStatus());
        $this->assertSame('awarded', $this->tender(['tender_deadline' => '2026-10-01', 'tender_result' => 'Wybrano firmę X.'], 'd')->tenderStatus());
        $this->assertSame('cancelled', $this->tender(['tender_status' => 'cancelled', 'tender_deadline' => '2026-10-20'], 'e')->tenderStatus());
        $this->assertSame('open', $this->tender([], 'f')->tenderStatus());
    }

    public function test_open_tender_shows_facts_criteria_notices_and_bk_button(): void
    {
        $this->tender([
            'lead' => 'Przeprowadzenie szkoleń z dostępności cyfrowej.',
            'tender_number' => 'ZO/3/2026',
            'tender_deadline' => '2026-10-16T10:00',
            'tender_cpv' => '80500000-9',
            'tender_bk_url' => 'https://bazakonkurencyjnosci.funduszeeuropejskie.gov.pl/ogloszenia/1',
            'requirements' => [['text' => 'Doświadczenie w 2 szkoleniach']],
            'criteria' => [['name' => 'Cena', 'weight' => '60%', 'text' => 'najniższa cena / cena oferty × 60']],
            'notices' => [
                ['date' => '2026-10-05', 'title' => 'Wyjaśnienie nr 1', 'text' => 'Odpowiedź na pytanie.'],
                ['date' => '2026-10-08', 'title' => 'Zmiana nr 1', 'text' => 'Zmiana terminu.'],
            ],
        ]);

        $html = $this->get('/zapytanie')->assertOk()
            ->assertSee('Przyjmowanie ofert')
            ->assertSee('ZO/3/2026')
            ->assertSee('Do terminu składania ofert: 5 dni.')
            ->assertSee('80500000-9')
            ->assertSee('Doświadczenie w 2 szkoleniach')
            ->assertSee('60%')
            ->assertSee('Złóż ofertę w Bazie Konkurencyjności')
            ->getContent();

        // Najnowsza zmiana na górze.
        $this->assertLessThan(strpos($html, 'Wyjaśnienie nr 1'), strpos($html, 'Zmiana nr 1'));
    }

    public function test_awarded_tender_shows_result_and_no_offer_button(): void
    {
        $this->tender([
            'tender_deadline' => '2026-10-01',
            'tender_bk_url' => 'https://bazakonkurencyjnosci.funduszeeuropejskie.gov.pl/ogloszenia/1',
            'tender_result' => 'Wybrano ofertę firmy Szkolenia sp. z o.o., cena 12 300 zł brutto.',
        ]);

        $this->get('/zapytanie')->assertOk()
            ->assertSee('Rozstrzygnięte')
            ->assertSee('Wynik postępowania')
            ->assertSee('Szkolenia sp. z o.o.')
            ->assertDontSee('Złóż ofertę w Bazie Konkurencyjności');
    }

    public function test_admin_renders_and_saves_tender_fields(): void
    {
        $page = $this->tender([], 'zapytanie-admin');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('data-type-fields="tender"', false)
            ->assertSee('name="type_data[criteria][__INDEX__][weight]"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Zapytanie', 'slug' => 'zapytanie-admin', 'type' => 'tender', 'parent_id' => '', 'is_published' => '1',
            'type_data' => [
                'tender_number' => 'ZO/4/2026',
                'tender_deadline' => '2026-10-30T10:00',
                'criteria' => [['name' => 'Cena', 'weight' => '100%']],
                'notices' => [['date' => '2026-10-12', 'title' => 'Wyjaśnienie nr 1']],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $td = $page->fresh()->typeData();
        $this->assertSame('ZO/4/2026', $td['tender_number']);
        $this->assertSame('100%', $td['criteria'][0]['weight']);
        $this->assertSame('Wyjaśnienie nr 1', $td['notices'][0]['title']);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Zapytanie', 'slug' => 'zapytanie-admin', 'type' => 'tender', 'parent_id' => '',
            'type_data' => ['tender_bk_url' => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('type_data.tender_bk_url');
    }
}
