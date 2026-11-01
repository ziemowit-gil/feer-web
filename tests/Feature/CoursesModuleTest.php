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
            ->assertSee('poniedziałki 17:00-19:00')->assertSee('Wolne miejsca: 2 z 2')->assertSee('Potrzeby i dostosowania');
    }

    public function test_strona_kursu_ma_czytelny_uklad_z_tabela_terminow_i_spisem_sekcji(): void
    {
        $c = $this->course(['description' => '<p>Opis szkolenia.</p>', 'audience' => "Osoby niewidome\nOsoby słabowidzące"]);
        $this->edition($c, ['location' => 'Warszawa']);

        $html = $this->get('/zajecia/czytnik')->assertOk()
            ->assertSee('Na tej stronie')->assertSee('Terminy szkolenia Czytnik ekranu od podstaw')->assertSee('Warszawa')
            ->getContent();

        $this->assertStringContainsString('<table class="cs-table">', $html);
        $this->assertStringContainsString('href="#terminy"', $html);
        $this->assertStringNotContainsString('cs-edition', $html, 'terminy to tabela, nie karty');
    }

    public function test_strona_kursu_ma_blok_terminu_pasek_mobilny_kontakt_i_zobacz_takze(): void
    {
        $this->site->forceFill(['contact_phone' => '12 616 7555', 'contact_email' => 'biuro@example.com'])->save();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $c = $this->course();
        $this->edition($c, ['capacity' => 10]);
        $this->course(['title' => 'Excel dla każdego', 'slug' => 'excel', 'category' => 'm365']);

        $this->get('/zajecia/czytnik')->assertOk()
            ->assertSee('Najbliższy termin')->assertSee('id="cs-sticky"', false)
            ->assertSee('Wolne miejsca: 10 z 10')->assertSee('tel:126167555', false)->assertSee('biuro@example.com')
            ->assertSee('Zobacz także')->assertSee('Excel dla każdego')
            ->assertSee('Pobierz kartę szkolenia (PDF)')->assertSee('Rozmiar tekstu')->assertSee('Dodaj do kalendarza');
    }

    public function test_ostatnie_miejsca_sa_opisane_tekstem(): void
    {
        $c = $this->course();
        $this->edition($c, ['capacity' => 6])->registrations()->createMany(
            collect(range(1, 4))->map(fn ($i) => ['first_name' => 'A', 'last_name' => 'B', 'email' => "u$i@example.com", 'status' => 'confirmed', 'source' => 'web'])->all()
        );

        $this->get('/zajecia/czytnik')->assertOk()->assertSee('Wolne miejsca: 2 z 6 (ostatnie miejsca)');
    }

    public function test_termin_do_kalendarza_zwraca_plik_ics(): void
    {
        $c = $this->course();
        $e = $this->edition($c, ['location' => 'Warszawa, ul. Długa 1', 'starts_on' => '2027-03-10', 'ends_on' => '2027-03-12']);
        $other = $this->course(['title' => 'Inne', 'slug' => 'inne']);

        $res = $this->get("/zajecia/czytnik/termin/{$e->id}.ics")->assertOk();
        $this->assertStringContainsString('text/calendar', $res->headers->get('Content-Type'));
        $body = $res->getContent();
        $this->assertStringContainsString('BEGIN:VEVENT', $body);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20270310', $body);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20270313', $body, 'koniec całodniowego wydarzenia jest dniem po ostatnim');
        $this->assertStringContainsString('LOCATION:Warszawa\\, ul. Długa 1', $body);

        $this->get("/zajecia/{$other->slug}/termin/{$e->id}.ics")->assertNotFound();
    }

    public function test_karta_szkolenia_pobiera_sie_jako_pdf(): void
    {
        $c = $this->course(['description' => '<p>Opis szkolenia.</p>']);
        $this->edition($c);

        $res = $this->get('/zajecia/czytnik/karta.pdf')->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_powiadom_mnie_zapisuje_adres_bez_duplikatow_i_wymaga_zgody(): void
    {
        $c = $this->course();

        $challenge = SpamGuard::challenge();
        $this->travel(10)->seconds();
        $payload = [
            'notify_email' => 'Ola@Example.com', 'notify_consent' => 1,
            SpamGuard::TOKEN_FIELD => $challenge['token'], SpamGuard::ANSWER_FIELD => (string) $this->answer($challenge['question']),
        ];

        \App\Models\CourseNotification::create(['course_id' => $c->id, 'email' => 'ola@example.com']);

        $this->post('/zajecia/czytnik/powiadom', $payload)->assertRedirect(route('courses.show', $c))->assertSessionHas('status');
        $this->assertSame(1, \App\Models\CourseNotification::where('course_id', $c->id)->where('email', 'ola@example.com')->count());

        $this->post('/zajecia/czytnik/powiadom', ['notify_email' => 'zly', 'notify_consent' => null] + $payload)->assertSessionHasErrors(['notify_email', 'notify_consent']);
    }

    public function test_bez_terminow_strona_pokazuje_formularz_powiadomienia(): void
    {
        $this->course();

        $this->get('/zajecia/czytnik')->assertOk()->assertSee('Powiadom mnie o terminie')->assertSee('name="notify_email"', false);
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

    public function test_panel_pokazuje_liste_powiadomien_wysyla_jednorazowo_i_usuwa(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $c = $this->course();
        $e = $this->edition($c);
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorVerified::class);
        $this->actingAs($admin);

        $a = \App\Models\CourseNotification::create(['course_id' => $c->id, 'email' => 'ala@example.com']);
        \App\Models\CourseNotification::create(['course_id' => $c->id, 'email' => 'bartek@example.com']);

        $this->get(route('admin.kursy.edit', $c))->assertOk()
            ->assertSee('Powiadomienia o terminie')->assertSee('ala@example.com')->assertSee('Czeka na powiadomienie: 2');

        $this->post(route('admin.kursy.notifications.send', $c), ['edition_id' => $e->id])->assertRedirect()->assertSessionHas('status');
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CourseNewEditionMail::class, 2);
        $this->assertSame(0, \App\Models\CourseNotification::whereNull('notified_at')->count());

        // Drugi raz nikt nie dostaje wiadomości.
        $this->post(route('admin.kursy.notifications.send', $c), ['edition_id' => $e->id])->assertRedirect();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CourseNewEditionMail::class, 2);

        $this->delete(route('admin.kursy.notifications.destroy', $a))->assertRedirect();
        $this->assertDatabaseMissing('course_notifications', ['email' => 'ala@example.com']);
    }

    public function test_powiadomienie_nie_wychodzi_dla_terminu_bez_rekrutacji(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $c = $this->course();
        $closed = $this->edition($c, ['enrollment_status' => 'closed']);
        \App\Models\CourseNotification::create(['course_id' => $c->id, 'email' => 'ala@example.com']);
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorVerified::class);
        $this->actingAs($admin);

        $this->post(route('admin.kursy.notifications.send', $c), ['edition_id' => $closed->id])->assertSessionHasErrors('edition_id');
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_mail_o_nowym_terminie_ma_date_miejsce_i_link_do_zapisu(): void
    {
        $c = $this->course();
        $e = $this->edition($c, ['location' => 'Kraków']);

        $html = (new \App\Mail\CourseNewEditionMail($c, $e, 'FEER'))->render();

        $this->assertStringContainsString('Czytnik ekranu od podstaw', $html);
        $this->assertStringContainsString('Kraków', $html);
        $this->assertStringContainsString('termin='.$e->id.'#zapis', $html);
        $this->assertStringContainsString('jednorazowa', $html);
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

    public function test_filtr_kategorii_inne_jest_tylko_poza_feer_is(): void
    {
        $this->course(['category' => 'other', 'title' => 'Kurs z innej kategorii', 'slug' => 'inny']);

        $this->get('/zajecia')->assertOk()->assertSee('>Inne<', false);

        $this->site->forceFill(['site_template' => 'feer', 'theme_variant' => 'feer_is'])->save();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $this->get('/zajecia')->assertOk()->assertDontSee('>Inne<', false)->assertSee('Kurs z innej kategorii')->assertSee('Tyfloinformatyka');
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
