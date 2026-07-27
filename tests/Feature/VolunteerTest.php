<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VolunteerAd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function ad(array $overrides = []): VolunteerAd
    {
        return VolunteerAd::create(array_merge([
            'title' => 'Wolontariusz/ka w Klubie Cyfrowym',
            'slug' => 'klub-cyfrowy',
            'lead' => 'Pomóż osobom starszym oswoić smartfon.',
            'q_beneficiaries' => 'Osoby 60+ uczące się obsługi telefonu.',
            'q_tasks' => ['Prowadzenie spotkań', 'Przygotowanie materiałów'],
            'q_mode' => 'stacjonarnie',
            'q_location' => 'Nowy Sącz',
            'q_schedule' => 'Wtorki 16:00–18:00',
            'q_time_commitment' => '4 godziny tygodniowo, min. 3 miesiące',
            'q_benefits' => ['Zaświadczenie', 'Szkolenie WCAG'],
            'q_how_to_apply' => 'Wypełnij formularz — odpowiemy w 3 dni.',
            'application_url' => 'https://forms.example.com/wolontariat',
            'is_published' => true,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Wolontariusz/ka w Klubie Cyfrowym',
            'lead' => 'Pomóż osobom starszym oswoić smartfon.',
            'q_beneficiaries' => 'Osoby 60+ uczące się obsługi telefonu.',
            'q_tasks' => "Prowadzenie spotkań\nPrzygotowanie materiałów",
            'q_mode' => 'stacjonarnie',
            'q_location' => 'Nowy Sącz',
            'q_schedule' => 'Wtorki 16:00–18:00',
            'q_time_commitment' => '4 godziny tygodniowo',
            'q_benefits' => "Zaświadczenie\nSzkolenie WCAG",
            'q_how_to_apply' => 'Wypełnij formularz — odpowiemy w 3 dni.',
            'application_url' => 'https://forms.example.com/wolontariat',
            'audience' => 'brand',
            'is_published' => '1',
        ], $overrides);
    }

    public function test_public_list_and_detail_render(): void
    {
        $ad = $this->ad();

        $this->get(route('volunteer.index'))->assertOk()->assertSee('Wolontariusz/ka w Klubie Cyfrowym');

        $this->get(route('volunteer.show', $ad))
            ->assertOk()
            ->assertSee('Cel wolontariatu')
            ->assertSee('Na czym polega wolontariat?')
            ->assertSee('Co zyskasz?')
            ->assertSee('Jak się zgłosić?')
            ->assertSee('Prowadzenie spotkań');
    }

    public function test_unpublished_ad_is_not_publicly_visible(): void
    {
        $ad = $this->ad(['is_published' => false]);

        $this->get(route('volunteer.show', $ad))->assertNotFound();
    }

    public function test_admin_can_create_ad_and_lists_are_split_into_arrays(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wolontariat.store'), $this->payload())
            ->assertRedirect(route('admin.wolontariat.index'));

        $ad = VolunteerAd::firstWhere('title', 'Wolontariusz/ka w Klubie Cyfrowym');

        $this->assertNotNull($ad);
        $this->assertSame(['Prowadzenie spotkań', 'Przygotowanie materiałów'], $ad->q_tasks);
        $this->assertSame(['Zaświadczenie', 'Szkolenie WCAG'], $ad->q_benefits);
        $this->assertTrue($ad->is_published);
        $this->assertSame('wolontariuszka-w-klubie-cyfrowym', $ad->slug);
    }

    public function test_each_of_the_six_questions_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wolontariat.store'), $this->payload([
                'q_benefits' => '', // pytanie 5 puste
            ]))
            ->assertSessionHasErrors('q_benefits');
    }

    public function test_empty_cliches_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.wolontariat.store'), $this->payload([
                'lead' => 'Potrzebujemy pomocy, zapraszamy chętnych.',
            ]))
            ->assertSessionHasErrors('lead');
    }

    public function test_volunteering_nav_type_points_to_the_listing(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pozycje-menu.store'), [
                'label' => 'Zostań wolontariuszem',
                'type' => 'volunteering',
                'location' => 'main',
                'is_button' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item = NavItem::firstWhere('label', 'Zostań wolontariuszem');

        $this->assertNotNull($item);
        $this->assertSame(url('/wolontariat'), $item->url);
        $this->assertSame('volunteering', $item->module);
        $this->assertTrue($item->is_button);
    }
}
