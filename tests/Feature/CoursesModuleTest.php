<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\CourseRegistration;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduł „Szkolenia i zajęcia”: oferta, zapisy z listą rezerwową, panel koordynatora i integracja z wariantem FEER IS. */
class CoursesModuleTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $site;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->site = SiteSetting::current();
    }

    private function course(array $extra = []): Course
    {
        return Course::create($extra + ['title' => 'Czytnik ekranu od podstaw', 'slug' => 'czytnik', 'lead' => 'Nauka NVDA.', 'category' => 'tyflo', 'format' => 'online', 'price' => 120, 'status' => 'active']);
    }

    private function edition(Course $c, array $extra = []): CourseEdition
    {
        return $c->editions()->create($extra + ['starts_on' => now()->addWeek(), 'capacity' => 2, 'enrollment_status' => 'open', 'schedule' => 'poniedziałki 17:00-19:00']);
    }

    private function payload(CourseEdition $e, array $extra = []): array
    {
        $challenge = SpamGuard::challenge();
        $this->travel(10)->seconds();

        return $extra + [
            'edition_id' => $e->id, 'first_name' => 'Anna', 'last_name' => 'Nowak', 'email' => 'anna@example.com', 'consent' => 1,
            SpamGuard::TOKEN_FIELD => $challenge['token'],
            SpamGuard::ANSWER_FIELD => (string) $this->answer($challenge['question']),
        ];
    }

    private function answer(string $question): int
    {
        preg_match('/(\d+)\s*([+\-−×x*])\s*(\d+)/u', $question, $m);

        return match ($m[2]) {
            '+' => $m[1] + $m[3],
            '-', '−' => $m[1] - $m[3],
            default => $m[1] * $m[3],
        };
    }

    public function test_lista_filtruje_po_kategorii_i_ukrywa_szkice(): void
    {
        $this->edition($this->course());
        $this->course(['title' => 'Szkic', 'slug' => 'szkic', 'status' => 'draft']);
        $this->course(['title' => 'Excel', 'slug' => 'excel', 'category' => 'm365', 'format' => 'stationary']);

        $this->get('/zajecia')->assertOk()->assertSee('Czytnik ekranu od podstaw')->assertSee('Excel')->assertDontSee('Szkic');
        $this->get('/zajecia?kategoria=m365')->assertOk()->assertSee('Excel')->assertDontSee('Czytnik ekranu od podstaw');
        $this->get('/zajecia?format=online')->assertOk()->assertSee('Czytnik ekranu od podstaw')->assertDontSee('Excel');
        $this->get('/zajecia/szkic')->assertNotFound();
    }

    public function test_karta_pokazuje_terminy_i_wolne_miejsca(): void
    {
        $c = $this->course();
        $this->edition($c);
        $this->edition($c, ['starts_on' => now()->subMonth()]);

        $this->get('/zajecia/czytnik')->assertOk()
            ->assertSee('poniedziałki 17:00-19:00')->assertSee('wolne 2 z 2')->assertSee('Potrzeby i dostosowania');
    }

    public function test_zapis_do_limitu_potem_lista_rezerwowa(): void
    {
        $e = $this->edition($this->course(), ['capacity' => 1]);

        $this->post('/zajecia/czytnik/zapis', $this->payload($e))->assertRedirect();
        $first = CourseRegistration::firstOrFail();
        $this->assertSame('pending', $first->status);
        $this->assertMatchesRegularExpression('/^SZK-[A-Z2-9]{6}$/', $first->payment_reference);

        $this->post('/zajecia/czytnik/zapis', $this->payload($e, ['email' => 'jan@example.com', 'first_name' => 'Jan', 'accessibility_needs' => 'Czytnik ekranu']))->assertRedirect();
        $second = CourseRegistration::where('email', 'jan@example.com')->firstOrFail();
        $this->assertSame('waitlist', $second->status);
        $this->assertSame('Czytnik ekranu', $second->accessibility_needs);
        $this->assertSame(0, $e->fresh()->free_seats);
    }

    public function test_ten_sam_email_nie_zapisze_sie_dwa_razy_na_termin(): void
    {
        $e = $this->edition($this->course());
        $e->registrations()->create(['first_name' => 'Anna', 'last_name' => 'Nowak', 'email' => 'anna@example.com', 'status' => 'pending']);
        $this->post('/zajecia/czytnik/zapis', $this->payload($e))->assertSessionHasErrors('edition_id');
        $this->assertSame(1, CourseRegistration::count());
    }

    public function test_walidacja_i_zamknieta_rekrutacja(): void
    {
        $e = $this->edition($this->course());
        $this->post('/zajecia/czytnik/zapis', $this->payload($e, ['email' => 'zly', 'consent' => null]))->assertSessionHasErrors(['email', 'consent']);

        $e->update(['enrollment_status' => 'closed']);
        $this->post('/zajecia/czytnik/zapis', $this->payload($e))->assertSessionHasErrors('edition_id');
        $this->assertSame(0, CourseRegistration::count());
    }

    public function test_anulowane_zgloszenie_zwalnia_miejsce(): void
    {
        $e = $this->edition($this->course(), ['capacity' => 1]);
        $r = $e->registrations()->create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.pl', 'status' => 'confirmed']);
        $this->assertTrue($e->fresh()->isFull());
        $r->update(['status' => 'cancelled']);
        $this->assertSame(1, $e->fresh()->free_seats);
    }

    public function test_panel_dodaje_uczestnika_zmienia_status_i_pilnuje_limitu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $e = $this->edition($this->course(), ['capacity' => 1]);

        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorVerified::class);
        $this->actingAs($admin);

        $row = ['first_name' => 'Ewa', 'last_name' => 'Kowal', 'email' => 'ewa@example.com', 'status' => 'confirmed', 'source' => 'phone'];
        $this->post(route('admin.kursy.registrations.store', $e), $row)->assertRedirect();
        $this->post(route('admin.kursy.registrations.store', $e), ['email' => 'ola@example.com', 'first_name' => 'Ola'] + $row);
        $this->assertSame('confirmed', CourseRegistration::where('email', 'ewa@example.com')->value('status'));
        $this->assertSame('waitlist', CourseRegistration::where('email', 'ola@example.com')->value('status'));

        $ola = CourseRegistration::where('email', 'ola@example.com')->first();
        $this->put(route('admin.kursy.registrations.status', $ola), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame('cancelled', $ola->fresh()->status);

        $this->get(route('admin.kursy.registrations.index', $e))->assertOk()->assertSee('Ewa Kowal');
    }

    public function test_feer_is_pokazuje_najblizsze_szkolenia_na_stronie_glownej_i_naglowek_portalu(): void
    {
        $this->site->forceFill(['site_template' => 'feer', 'theme_variant' => 'feer_is'])->save();
        $e = $this->edition($this->course());
        $this->edition($this->course(['title' => 'Archiwalne', 'slug' => 'arch', 'status' => 'finished']));

        $this->get('/')->assertOk()->assertSee('Najbliższe szkolenia')->assertSee('Czytnik ekranu od podstaw')->assertDontSee('Archiwalne')
            ->assertSee('/zajecia/czytnik?termin='.$e->id, false);

        $this->get('/zajecia')->assertOk()->assertSee('bg-brand-dark', false);
    }

    private function subSiteWithCourse(): void
    {
        $sub = SiteSetting::create(['site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'parent_site_id' => $this->site->id, 'site_kind' => 'standard', 'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is']);
        $this->course();
        (new Course(['title' => 'Tylko podwitryna', 'slug' => 'sub', 'category' => 'other', 'format' => 'online', 'status' => 'active']))->forceFill(['site_id' => $sub->id])->save();
    }

    public function test_witryna_glowna_nie_widzi_szkolen_podwitryny(): void
    {
        $this->subSiteWithCourse();
        $this->get('/zajecia')->assertOk()->assertSee('Czytnik ekranu od podstaw')->assertDontSee('Tylko podwitryna');
    }

    public function test_podwitryna_pod_prefiksem_widzi_tylko_swoje_szkolenia(): void
    {
        $this->subSiteWithCourse();
        $this->get('/szkolenia/zajecia')->assertOk()->assertSee('Tylko podwitryna')->assertDontSee('Czytnik ekranu od podstaw');
    }
}
