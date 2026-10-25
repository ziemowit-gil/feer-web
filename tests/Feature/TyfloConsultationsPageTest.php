<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\PageTypes\PageTypeRegistry;
use App\Support\TyfloSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Typ strony „Konsultacje tyflo” (app/PageTypes/Types/TyfloConsultations): pola w panelu,
 * walidacja i zapis harmonogramu w type_data, widok publiczny z dostępną tabelą terminów.
 */
class TyfloConsultationsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        Carbon::setTestNow('2026-10-26 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function consultations(array $typeData = [], array $attributes = []): Page
    {
        return Page::create($attributes + [
            'title' => 'Konsultacje tyflopedagogiczne',
            'slug' => 'konsultacje-tyflo',
            'type' => 'tyflo_consultations',
            'content' => '<p>Indywidualne konsultacje dla osób z dysfunkcją wzroku.</p>',
            'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    private function fullData(): array
    {
        return [
            'lead' => 'Wsparcie tyflopedagogów dla osób niewidomych i słabowidzących.',
            'audience' => [['text' => 'Osoby niewidome'], ['text' => 'Osoby słabowidzące']],
            'consultants' => [['name' => 'Anna Nowak', 'role' => 'Tyflopedagog', 'text' => 'Orientacja przestrzenna.', 'email' => 'anna@example.org']],
            'contact_name' => 'Biuro FEER',
            'contact_phone' => '+48 12 345 67 89',
            'contact_email' => 'tyflo@example.org',
            'tc_signup_note' => 'Zgłoś się co najmniej 2 dni przed terminem.',
            'tc_form' => 'zapis-tyflo',
            'tc_schedule_note' => 'Konsultacja z 10 listopada przeniesiona na 12 listopada.',
            'tc_accessibility' => 'Biuro bez barier, możliwość wejścia z psem przewodnikiem.',
            'slots' => [
                ['date' => '2026-11-12', 'time_from' => '10:00', 'time_to' => '12:00', 'mode' => 'online', 'place' => 'Zoom — link po zgłoszeniu', 'consultant' => 'Anna Nowak', 'seats' => '5', 'status' => 'open', 'note' => 'Prosimy o czytnik ekranu'],
                ['date' => '2026-11-05', 'time_from' => '09:00', 'time_to' => '11:00', 'mode' => 'stationary', 'place' => 'Biuro FEER, sala 2', 'seats' => '2', 'status' => 'full'],
                ['date' => '2026-10-01', 'time_from' => '10:00', 'time_to' => '12:00', 'mode' => 'phone', 'status' => 'open'],
                ['date' => '2026-11-20', 'time_from' => '14:00', 'mode' => 'online', 'status' => 'cancelled'],
            ],
        ];
    }

    public function test_type_is_registered_as_type_data_type_with_existing_views(): void
    {
        $type = PageTypeRegistry::get('tyflo_consultations');

        $this->assertNotNull($type);
        $this->assertTrue($type->usesTypeData());
        $this->assertFalse($type->standardLayout());
        $this->assertSame('Oferta i edukacja', $type->group());
        $this->assertArrayHasKey('tyflo_consultations', Page::types());
        $this->assertContains('slots', $type->listKeys());
        $this->assertTrue(\Illuminate\Support\Facades\View::exists($type->frontView()));
        $this->assertTrue(\Illuminate\Support\Facades\View::exists($type->adminView()));
    }

    public function test_admin_form_offers_the_type_and_schedule_fields(): void
    {
        $page = $this->consultations($this->fullData());

        $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('<option value="tyflo_consultations" selected>', false)
            ->assertSee('data-type-fields="tyflo_consultations"', false)
            ->assertSee('name="type_data[consultants][0][name]"', false)
            ->assertSee('name="type_data[slots][0][date]"', false)
            ->assertSee('name="type_data[slots][3][status]"', false)
            ->assertSee('name="type_data[slots][__INDEX__][mode]"', false)
            ->assertSee('name="type_data[tc_form]"', false)
            ->assertSee('Harmonogram terminów')
            // Pola terminu mają widoczne etykiety (label owija pole).
            ->assertSee('Miejsce lub sposób połączenia')
            ->assertSee('Liczba miejsc');
    }

    public function test_admin_saves_schedule_and_drops_empty_rows(): void
    {
        $page = $this->consultations();
        $payload = ['title' => 'Konsultacje tyflopedagogiczne', 'slug' => 'konsultacje-tyflo', 'type' => 'tyflo_consultations', 'parent_id' => '', 'is_published' => '1'];

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), $payload + [
            'type_data' => [
                'lead' => 'Lead',
                'slots' => [
                    ['date' => '2026-11-12', 'time_from' => '10:00', 'time_to' => '12:00', 'mode' => 'online', 'place' => 'Zoom', 'seats' => '5', 'status' => 'open', 'note' => ''],
                    ['date' => '', 'time_from' => '', 'time_to' => '', 'mode' => '', 'place' => '', 'consultant' => '', 'seats' => '', 'status' => '', 'note' => ''],
                ],
                'consultants' => [['name' => 'Anna Nowak', 'role' => '', 'text' => '', 'email' => '']],
                'undeclared' => 'odrzucone',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertCount(1, $page->typeData()['slots']);
        $this->assertSame('online', $page->typeData()['slots'][0]['mode']);
        $this->assertSame('Anna Nowak', $page->typeData()['consultants'][0]['name']);
        $this->assertArrayNotHasKey('undeclared', $page->type_data);
    }

    public function test_admin_rejects_invalid_schedule_values(): void
    {
        $page = $this->consultations();
        $payload = ['title' => 'Konsultacje', 'slug' => 'konsultacje-tyflo', 'type' => 'tyflo_consultations', 'parent_id' => '', 'is_published' => '1'];

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), $payload + [
            'type_data' => ['slots' => [['date' => 'jutro', 'time_from' => '25:99', 'mode' => 'teleportacja', 'status' => 'nieznany']]],
        ])->assertSessionHasErrors([
            'type_data.slots.0.date',
            'type_data.slots.0.time_from',
            'type_data.slots.0.mode',
            'type_data.slots.0.status',
        ]);
    }

    public function test_block_editor_shows_and_saves_the_type_fields(): void
    {
        $page = $this->consultations();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()
            ->assertSee('data-type-fields="tyflo_consultations"', false)
            ->assertSee('name="type_data[slots][__INDEX__][date]"', false);

        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => $page->title,
            'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'Opis konsultacji']]],
            'fields' => ['type_data' => ['slots' => [['date' => '2026-11-12', 'status' => 'few', 'seats' => '1']]]],
        ])->assertOk();

        $slot = $page->fresh()->typeData()['slots'][0];
        $this->assertSame('2026-11-12', $slot['date']);
        $this->assertSame('few', $slot['status']);
    }

    public function test_public_page_renders_accessible_schedule_table(): void
    {
        $this->consultations($this->fullData());

        $response = $this->get('/konsultacje-tyflo')->assertOk();

        $response->assertSee('data-page-type="tyflo_consultations"', false)
            ->assertSee('Konsultacje tyflopedagogiczne')
            ->assertSee('Wsparcie tyflopedagogów')
            ->assertSee('Indywidualne konsultacje dla osób z dysfunkcją wzroku.')
            // Skróty do sekcji i sekcje z nagłówkami.
            ->assertSee('aria-label="Na tej stronie"', false)
            ->assertSee('href="#harmonogram"', false)
            ->assertSee('id="harmonogram"', false)
            ->assertSee('Harmonogram konsultacji')
            // Semantyczna tabela: caption, nagłówki kolumn i wierszy, fokusowalny region przewijany.
            ->assertSee('<caption class="sr-only">Nadchodzące terminy konsultacji tyflo', false)
            ->assertSee('<th scope="col" class="px-4 py-3">Data</th>', false)
            ->assertSee('<th scope="col" class="px-4 py-3">Status</th>', false)
            ->assertSee('<th scope="row"', false)
            ->assertSee('role="region" tabindex="0" aria-labelledby="tc-schedule"', false)
            ->assertSee('<time datetime="2026-11-05">czwartek, 5 listopada 2026</time>', false)
            // Status, forma i miejsce — tekstem.
            ->assertSee('Wolne miejsca')
            ->assertSee('Brak miejsc')
            ->assertSee('Odwołane')
            ->assertSee('Stacjonarnie')
            ->assertSee('Biuro FEER, sala 2')
            ->assertSee('Zoom — link po zgłoszeniu')
            ->assertSee('Prosimy o czytnik ekranu')
            // Konsultant i uwagi mają kolumny, bo występują w terminach.
            ->assertSee('<th scope="col" class="px-4 py-3">Konsultant</th>', false)
            ->assertSee('<th scope="col" class="px-4 py-3">Uwagi</th>', false)
            // Informacja o zmianie, zgłoszenie, dostępność, konsultanci.
            ->assertSee('Konsultacja z 10 listopada przeniesiona na 12 listopada.')
            ->assertSee('Zgłoś się co najmniej 2 dni przed terminem.')
            ->assertSee('href="tel:+48123456789"', false)
            ->assertSee('href="mailto:tyflo@example.org"', false)
            ->assertSee('Anna Nowak')
            ->assertSee('Biuro bez barier, możliwość wejścia z psem przewodnikiem.')
            ->assertSee('id="zgloszenie"', false);

        // Nadchodzące rosnąco po dacie; zakończony (1 października) tylko w zwijanej liście, na końcu.
        preg_match_all('/<th scope="row"[^>]*>\s*<time datetime="([^"]+)"/', $response->getContent(), $dates);
        $this->assertSame(['2026-11-05', '2026-11-12', '2026-11-20', '2026-10-01'], $dates[1]);
        $this->assertStringContainsString('Zakończone terminy (1)', $response->getContent());
    }

    public function test_next_free_slot_and_sign_up_link_skip_full_and_cancelled_terms(): void
    {
        $this->consultations($this->fullData());

        $response = $this->get('/konsultacje-tyflo')->assertOk();

        // Najbliższy WOLNY termin to 12 listopada (5 listopada: brak miejsc).
        $response->assertSee('Najbliższy wolny termin')
            ->assertSee('Zapisz się na konsultacje')
            ->assertSee('href="#zgloszenie"', false)
            // Link w wierszu ma kontekst dla czytnika ekranu i występuje tylko przy wolnych terminach.
            ->assertSee('Zapisz się<span class="sr-only"> na konsultacje: czwartek, 12 listopada 2026, godz. 10:00</span>', false);
        $this->assertSame(1, substr_count($response->getContent(), 'class="sr-only"> na konsultacje:'));
    }

    public function test_page_without_schedule_shows_empty_state_and_hides_optional_sections(): void
    {
        $this->consultations(['lead' => 'Krótki opis.'], ['content' => null]);

        $this->get('/konsultacje-tyflo')->assertOk()
            ->assertSee('Nowe terminy konsultacji zostaną wkrótce opublikowane.')
            ->assertSee('Obecnie nie ma wolnych terminów.')
            ->assertDontSee('<table', false)
            ->assertDontSee('id="konsultanci"', false)
            ->assertDontSee('id="jak-sie-zgloszyc"', false)
            ->assertDontSee('id="zgloszenie"', false)
            ->assertDontSee('Zakończone terminy');
    }

    public function test_unsafe_cta_url_is_ignored(): void
    {
        $this->consultations(['cta_url' => 'javascript:alert(1)', 'slots' => [['date' => '2026-11-12', 'status' => 'open']]]);

        $this->get('/konsultacje-tyflo')->assertOk()
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('Zapisz się<span', false);
    }

    public function test_schedule_helper_sorts_classifies_and_normalizes(): void
    {
        $slots = TyfloSchedule::slots([
            ['date' => '2026-10-26', 'time_from' => '09:00', 'time_to' => '11:00', 'status' => 'open'],   // dziś, już po końcu
            ['date' => '2026-10-26', 'time_from' => '15:00', 'time_to' => '16:00', 'status' => 'open'],   // dziś, jeszcze przed nami
            ['date' => '2026-10-27', 'time_from' => '08:00', 'status' => 'nieznany', 'mode' => 'xyz'],     // zły status/forma → domyślne
            ['date' => '', 'time_from' => '10:00'],                                                       // bez daty → pominięty
            'nie-wiersz',
        ]);

        $this->assertCount(3, $slots);
        $this->assertSame(['2026-10-26', '2026-10-26', '2026-10-27'], $slots->map(fn ($s) => $s['date']->format('Y-m-d'))->all());
        $this->assertTrue($slots[0]['past']);
        $this->assertFalse($slots[0]['bookable']);
        $this->assertFalse($slots[1]['past']);
        $this->assertTrue($slots[1]['bookable']);
        $this->assertSame('15:00 – 16:00', $slots[1]['time_label']);
        $this->assertSame('open', $slots[2]['status']);
        $this->assertNull($slots[2]['mode']);
        $this->assertSame('od 08:00', $slots[2]['time_label']);
    }

    public function test_views_use_no_low_contrast_or_color_only_classes(): void
    {
        foreach ([
            'resources/views/page/partials/typed/tyflo-consultations.blade.php',
            'resources/views/page/partials/typed/_tyflo-table.blade.php',
        ] as $file) {
            $source = file_get_contents(base_path($file));

            $this->assertDoesNotMatchRegularExpression('/\b(opacity-\d+|text-gray-[1-5]00|text-brand(?![-\w])|text-accent|bg-accent|border)\b/', $source, "Niedozwolona klasa w {$file}");
        }
    }
}
