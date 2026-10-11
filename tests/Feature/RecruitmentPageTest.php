<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecruitmentPageTest extends TestCase
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

    private function recruitment(array $typeData = [], string $slug = 'nabor'): Page
    {
        return Page::create([
            'title' => 'Nabór do projektu Praca bez barier',
            'slug' => $slug,
            'type' => 'recruitment',
            'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    public function test_status_is_computed_from_recruitment_dates(): void
    {
        $this->assertSame('planned', $this->recruitment(['rec_starts_at' => '2026-10-20T09:00'], 'a')->recruitmentStatus());
        $this->assertSame('open', $this->recruitment(['rec_starts_at' => '2026-10-01', 'rec_ends_at' => '2026-10-31'], 'b')->recruitmentStatus());
        // Sama data końca obowiązuje do końca dnia.
        $this->assertSame('open', $this->recruitment(['rec_ends_at' => '2026-10-11'], 'c')->recruitmentStatus());
        $this->assertSame('closed', $this->recruitment(['rec_ends_at' => '2026-10-11T08:00'], 'd')->recruitmentStatus());
        $this->assertSame('continuous', $this->recruitment([], 'e')->recruitmentStatus());
        // Ręczny status ma pierwszeństwo przed datami.
        $this->assertSame('suspended', $this->recruitment(['rec_status' => 'suspended', 'rec_ends_at' => '2026-10-31'], 'f')->recruitmentStatus());
    }

    public function test_open_recruitment_shows_facts_criteria_and_apply_button(): void
    {
        $this->recruitment([
            'lead' => 'Szukamy 40 osób.',
            'rec_starts_at' => '2026-10-01',
            'rec_ends_at' => '2026-10-15T16:00',
            'rec_places' => '40 osób',
            'requirements' => [['text' => 'Orzeczenie o niepełnosprawności']],
            'bonus' => [['text' => 'Zamieszkanie na wsi', 'points' => '+5 pkt']],
            'docs' => [['title' => 'Regulamin rekrutacji', 'url' => '/storage/regulamin.pdf']],
            'rec_accessibility' => 'Zgłoszenie przyjmiemy też w PJM.',
            'cta_label' => 'Zgłoś się',
            'cta_url' => 'https://example.org/zgloszenie',
        ]);

        $this->get('/nabor')->assertOk()
            ->assertSee('Nabór trwa')
            ->assertSee('Do końca naboru: 4 dni.')
            ->assertSee('40 osób')
            ->assertSee('Orzeczenie o niepełnosprawności')
            ->assertSee('+5 pkt')
            ->assertSee('Regulamin rekrutacji')
            ->assertSee('(PDF)', false)
            ->assertSee('Dostępność rekrutacji')
            ->assertSee('href="https://example.org/zgloszenie"', false)
            ->assertSee('otwiera się w nowej karcie');
    }

    public function test_closed_recruitment_hides_apply_button_and_shows_results(): void
    {
        $this->recruitment([
            'rec_ends_at' => '2026-10-01',
            'cta_label' => 'Zgłoś się',
            'cta_url' => '/zgloszenie',
            'rec_results' => 'Lista zakwalifikowanych do 15 października.',
        ]);

        $this->get('/nabor')->assertOk()
            ->assertSee('Nabór zakończony')
            ->assertSee('Lista zakwalifikowanych do 15 października.')
            ->assertDontSee('href="/zgloszenie"', false);
    }

    public function test_unsafe_cta_url_is_not_rendered(): void
    {
        $this->recruitment(['cta_label' => 'Zgłoś się', 'cta_url' => 'javascript:alert(1)']);

        $this->get('/nabor')->assertOk()->assertDontSee('javascript:', false);
    }

    public function test_admin_saves_recruitment_fields(): void
    {
        $page = $this->recruitment([], 'nabor-admin');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('data-type-fields="recruitment"', false)
            ->assertSee('name="type_data[bonus][__INDEX__][points]"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Nabór', 'slug' => 'nabor-admin', 'type' => 'recruitment', 'parent_id' => '', 'is_published' => '1',
            'type_data' => [
                'rec_status' => 'reserve',
                'cta_url' => '/lista-rezerwowa',
                'rec_starts_at' => '2026-10-01T09:00',
                'rec_ends_at' => '2026-10-31T16:00',
                'bonus' => [['text' => 'Wiek 50+', 'points' => '+3 pkt']],
                'rec_faq' => [['q' => 'Czy udział jest płatny?', 'a' => 'Nie.']],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('reserve', $page->recruitmentStatus());
        $this->assertSame('Wiek 50+', $page->typeData()['bonus'][0]['text']);
        $this->assertSame('Czy udział jest płatny?', $page->typeData()['rec_faq'][0]['q']);
        $this->get('/nabor-admin')->assertSee('Zapisz się na listę rezerwową', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Nabór', 'slug' => 'nabor-admin', 'type' => 'recruitment', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['rec_starts_at' => '2026-10-31', 'rec_ends_at' => '2026-10-01'],
        ])->assertSessionHasErrors('type_data.rec_ends_at');
    }
}
