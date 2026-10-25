<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\TyfloSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Typ „Szkolenia tyflo” (wspólny układ z konsultacjami — App\PageTypes\TyfloPageType): program,
 * prowadzący, wymagania, harmonogram z datą zakończenia; oraz osadzenie obu typów w działaniu.
 */
class TyfloTrainingsPageTest extends TestCase
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

    private function training(array $typeData = [], array $attributes = []): Page
    {
        return Page::create($attributes + [
            'title' => 'Szkolenie z czytnika ekranu',
            'slug' => 'szkolenie-tyflo',
            'type' => 'tyflo_trainings',
            'content' => '<p>Praktyczne szkolenie z obsługi komputera bez patrzenia na ekran.</p>',
            'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    private function data(): array
    {
        return [
            'lead' => 'Naucz się pracować z NVDA.',
            'tt_duration' => '16 godzin, 2 dni',
            'tt_cost' => 'Bezpłatnie',
            'tt_group' => 'do 6 osób',
            'tt_certificate' => 'Zaświadczenie o ukończeniu',
            'tt_program' => [['title' => 'Podstawy NVDA', 'text' => 'Skróty klawiszowe.'], ['title' => 'Poczta e-mail', 'text' => '']],
            'requirements' => [['text' => 'Własny laptop z NVDA']],
            'audience' => [['text' => 'Osoby niewidome']],
            'consultants' => [['name' => 'Jan Kowalski', 'role' => 'Trener tyflo']],
            'contact_email' => 'szkolenia@example.org',
            'slots' => [
                ['date' => '2026-11-14', 'date_to' => '2026-11-15', 'time_from' => '09:00', 'time_to' => '15:00', 'mode' => 'stationary', 'place' => 'Biuro FEER', 'consultant' => 'Jan Kowalski', 'seats' => '6', 'status' => 'few'],
                ['date' => '2026-10-10', 'time_from' => '09:00', 'time_to' => '15:00', 'mode' => 'online', 'status' => 'open'],
            ],
        ];
    }

    public function test_admin_form_has_training_fields_and_shared_schedule(): void
    {
        $page = $this->training($this->data());

        $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('<option value="tyflo_trainings" selected>', false)
            ->assertSee('data-type-fields="tyflo_trainings"', false)
            ->assertSee('name="type_data[tt_duration]"', false)
            ->assertSee('name="type_data[tt_program][0][title]"', false)
            ->assertSee('name="type_data[tt_program][__INDEX__][text]"', false)
            ->assertSee('name="type_data[slots][0][date_to]"', false)
            ->assertSee('Data zakończenia (szkolenie wielodniowe)')
            ->assertSee('Prowadzący (tyflopedagodzy, trenerzy, instruktorzy)');
    }

    public function test_admin_saves_training_fields_and_rejects_bad_values(): void
    {
        $page = $this->training();
        $payload = ['title' => 'Szkolenie', 'slug' => 'szkolenie-tyflo', 'type' => 'tyflo_trainings', 'parent_id' => '', 'is_published' => '1'];

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), $payload + ['type_data' => [
            'tt_cost' => 'Bezpłatnie',
            'tt_program' => [['title' => 'Moduł 1', 'text' => 'Opis'], ['title' => '', 'text' => '']],
            'requirements' => [['text' => 'Laptop']],
            'slots' => [['date' => '2026-11-14', 'date_to' => '2026-11-15', 'status' => 'open']],
        ]])->assertRedirect()->assertSessionHasNoErrors();

        $data = $page->fresh()->typeData();
        $this->assertSame('Bezpłatnie', $data['tt_cost']);
        $this->assertCount(1, $data['tt_program']);
        $this->assertSame('2026-11-15', $data['slots'][0]['date_to']);

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), $payload + ['type_data' => [
            'slots' => [['date' => '2026-11-14', 'date_to' => 'kiedyś']],
        ]])->assertSessionHasErrors('type_data.slots.0.date_to');
    }

    public function test_public_page_uses_training_labels_facts_program_and_date_range(): void
    {
        $this->training($this->data());

        $response = $this->get('/szkolenie-tyflo')->assertOk();

        $response->assertSee('data-page-type="tyflo_trainings"', false)
            ->assertSee('O szkoleniu')
            ->assertSee('Najważniejsze informacje')
            ->assertSee('16 godzin, 2 dni')
            ->assertSee('Zaświadczenie o ukończeniu')
            ->assertSee('Program szkolenia')
            ->assertSee('<ol class="list-decimal', false)
            ->assertSee('Podstawy NVDA')
            ->assertSee('Wymagania i co przygotować')
            ->assertSee('Własny laptop z NVDA')
            ->assertSee('Prowadzący')
            ->assertSee('Harmonogram szkoleń')
            ->assertSee('Najbliższy termin szkolenia')
            ->assertSee('<caption class="sr-only">Nadchodzące terminy szkoleń tyflo', false)
            ->assertSee('<th scope="col" class="px-4 py-3">Prowadzący</th>', false)
            // Szkolenie wielodniowe: data początku i zakończenia.
            ->assertSee('<time datetime="2026-11-14">sobota, 14 listopada 2026</time>', false)
            ->assertSee('<time datetime="2026-11-15">niedziela, 15 listopada 2026</time>', false)
            ->assertSee('Zakończone terminy (1)')
            ->assertDontSee('Harmonogram konsultacji');
    }

    public function test_multi_day_training_is_upcoming_until_its_last_day_ends(): void
    {
        $row = ['date' => '2026-10-25', 'date_to' => '2026-10-27', 'time_from' => '09:00', 'time_to' => '15:00', 'status' => 'open'];

        $this->assertFalse(TyfloSchedule::slots([$row])[0]['past']);
        Carbon::setTestNow('2026-10-27 15:01:00');
        $this->assertTrue(TyfloSchedule::slots([$row])[0]['past']);
        // Zła data zakończenia (przed początkiem) jest pomijana.
        $this->assertNull(TyfloSchedule::slots([['date' => '2026-10-25', 'date_to' => '2026-10-20']])[0]['date_to']);
    }

    public function test_consultations_page_keeps_consultation_labels(): void
    {
        $this->training(['slots' => [['date' => '2026-11-14', 'status' => 'open']]], ['type' => 'tyflo_consultations', 'slug' => 'k']);

        $this->get('/k')->assertOk()
            ->assertSee('Harmonogram konsultacji')
            ->assertSee('Najbliższy wolny termin')
            ->assertDontSee('Program szkolenia')
            ->assertDontSee('Najważniejsze informacje');
    }

    public function test_tyflo_pages_render_inside_a_project_as_tab_and_inline_section(): void
    {
        $category = Category::create(['name' => 'Dla osób z niepełnosprawnością wzroku', 'slug' => 'wzrok']);
        $project = Project::create(['title' => 'Tyflo dla wszystkich', 'slug' => 'tyflo', 'category_id' => $category->id, 'content' => '<p>Opis</p>', 'is_published' => true]);

        $this->training($this->data(), ['slug' => 'szkolenie-w-dzialaniu', 'project_id' => $project->id, 'project_display' => 'tab']);
        $this->training(['slots' => [['date' => '2026-11-20', 'time_from' => '10:00', 'status' => 'open']]], ['title' => 'Konsultacje w działaniu', 'slug' => 'konsultacje-w-dzialaniu', 'type' => 'tyflo_consultations', 'content' => '<p>Konsultacje</p>', 'project_id' => $project->id, 'project_display' => 'inline']);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        // Zakładka: treść strony raz (wypisuje ją widok działania), sekcje typu bez własnego nagłówka strony.
        $this->assertSame(1, substr_count($html, 'Praktyczne szkolenie z obsługi komputera'));
        $this->assertStringContainsString('data-page-type="tyflo_trainings" data-embedded', $html);
        $this->assertStringContainsString('Program szkolenia', $html);
        $this->assertStringContainsString('Harmonogram szkoleń', $html);
        $this->assertStringContainsString('<caption class="sr-only">Nadchodzące terminy szkoleń tyflo', $html);
        // Sekcja w treści: konsultacje, identyfikatory z numerem strony (bez kolizji).
        $this->assertStringContainsString('data-page-type="tyflo_consultations" data-embedded', $html);
        $this->assertMatchesRegularExpression('/id="tc-schedule-\d+"/', $html);
        $this->assertStringNotContainsString('id="tc-schedule"', $html);
        // Układ samodzielnej strony (skróty, kolumna boczna) nie trafia do działania.
        $this->assertStringNotContainsString('aria-label="Najbliższy termin i menu działu"', $html);
        $this->assertStringNotContainsString('aria-label="Na tej stronie"', $html);
    }

    public function test_page_tyflo_kind_is_set_only_for_tyflo_types(): void
    {
        $this->assertSame('trainings', (new Page(['type' => 'tyflo_trainings']))->tyfloKind());
        $this->assertSame('consultations', (new Page(['type' => 'tyflo_consultations']))->tyfloKind());
        $this->assertNull((new Page(['type' => 'schedule']))->tyfloKind());
        $this->assertNull((new Page(['type' => 'nieznany']))->tyfloKind());
    }
}
