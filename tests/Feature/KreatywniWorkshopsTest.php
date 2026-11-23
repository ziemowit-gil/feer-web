<?php

namespace Tests\Feature;

use App\Models\KreatywniWorkshop;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\PageTypes\PageTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mikro moduł „Warsztaty – Kreatywni” i typ strony „Kalendarz warsztatów”: panel (termin w czasie polskim, grafika
 * z opisem, kopia terminu, walidacja, widoczność), strona kalendarza (miesiące, karty, minione), typ do wyboru tylko
 * w szablonie „kreatywni”, działanie bez tabeli.
 */
class KreatywniWorkshopsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-11-23 12:00', 'Europe/Warsaw'));
        PageTypeRegistry::reset();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        PageTypeRegistry::reset();
        // Pamięć podręczna witryny trzyma szablon „kreatywni” — następne testy mają zaczynać od czystej.
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    private function useTemplate(string $template = 'kreatywni'): SiteSetting
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => $template])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        PageTypeRegistry::reset();

        return SiteSetting::current();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function workshop(string $title, string $start, ?string $end = null, array $extra = []): KreatywniWorkshop
    {
        return KreatywniWorkshop::create($extra + [
            'title' => $title, 'starts_at' => Carbon::parse($start, 'Europe/Warsaw')->utc(),
            'ends_at' => $end ? Carbon::parse($end, 'Europe/Warsaw')->utc() : null, 'is_active' => true,
        ]);
    }

    private function calendarPage(array $typeData = []): Page
    {
        return Page::create(['title' => 'Kalendarz warsztatów', 'slug' => 'kalendarz-warsztatow', 'type' => 'kreatywni_workshops',
            'is_published' => true, 'content' => '', 'type_data' => $typeData]);
    }

    public function test_panel_dodaje_warsztat_w_czasie_polskim_z_grafika(): void
    {
        $this->useTemplate();
        $this->actingAs($this->admin());

        $this->get(route('admin.kreatywni-warsztaty.index'))->assertOk()
            ->assertSee('Warsztaty – Kreatywni')->assertSee('Nie ma jeszcze strony z kalendarzem');
        $this->get(route('admin.kreatywni-warsztaty.create'))->assertOk()
            ->assertSee('<label for="kw-title"', false)->assertSee('<label for="kw-start"', false)
            ->assertSee('type="datetime-local"', false)->assertSee('name="image_library_id"', false);

        $this->post(route('admin.kreatywni-warsztaty.store'), [
            'title' => 'Świece sojowe', 'place' => 'Dom Kultury Podgórze, Kraków', 'starts_at' => '2026-12-12T10:00', 'ends_at' => '2026-12-12T13:00',
            'participants' => 'do 20 osób', 'description' => "Akapit pierwszy.\n\nAkapit drugi.", 'is_active' => '1',
            'image' => UploadedFile::fake()->image('swiece.jpg', 1200, 600), 'image_alt' => 'Kolorowe świece na stole',
        ])->assertRedirect(route('admin.kreatywni-warsztaty.index'))->assertSessionHasNoErrors();

        $w = KreatywniWorkshop::query()->sole();
        $this->assertSame('2026-12-12 09:00', $w->starts_at->format('Y-m-d H:i'), 'zimą czas polski = UTC+1');
        $this->assertSame('2026-12-12 12:00', $w->ends_at->format('Y-m-d H:i'));
        $this->assertSame('sobota, 12 grudnia 2026, 10:00–13:00', $w->whenLabel());
        $this->assertNotNull($w->imageUrl());
        $this->get(route('admin.kreatywni-warsztaty.edit', $w))->assertOk()->assertSee('value="2026-12-12T10:00"', false);
        $this->get(route('admin.kreatywni-warsztaty.index'))->assertSee('Świece sojowe')->assertSee('Nadchodzące i trwające <span class="font-normal text-muted">(1)</span>', false);
    }

    public function test_walidacja_terminu_i_grafiki(): void
    {
        $this->useTemplate();
        $this->actingAs($this->admin());

        $this->post(route('admin.kreatywni-warsztaty.store'), ['title' => 'X', 'starts_at' => '2026-12-12T10:00', 'ends_at' => '2026-12-12T09:00'])
            ->assertSessionHasErrors(['ends_at' => 'Koniec warsztatu musi być później niż początek.']);
        $this->post(route('admin.kreatywni-warsztaty.store'), ['title' => '', 'starts_at' => ''])->assertSessionHasErrors(['title', 'starts_at']);
        $this->post(route('admin.kreatywni-warsztaty.store'), ['title' => 'X', 'starts_at' => '2026-12-12T10:00', 'image' => UploadedFile::fake()->create('plik.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('image');
        $this->assertSame(0, KreatywniWorkshop::query()->count());
    }

    public function test_kopia_terminu_bierze_dane_i_grafike_oryginalu(): void
    {
        $this->useTemplate();
        $this->actingAs($this->admin());
        $source = $this->workshop('Dekorowanie bombek', '2026-12-05 10:00', '2026-12-05 12:00', ['place' => 'Świetlica', 'participants' => '15 osób']);
        $source->addMedia(UploadedFile::fake()->image('bombki.jpg', 800, 400))->toMediaCollection('image');

        $this->get(route('admin.kreatywni-warsztaty.create', ['kopia' => $source->id]))->assertOk()
            ->assertSee('Kopia warsztatu „Dekorowanie bombek”')->assertSee('value="Świetlica"', false)->assertSee('name="copy_of" value="'.$source->id.'"', false);
        $this->post(route('admin.kreatywni-warsztaty.store'), [
            'title' => 'Dekorowanie bombek', 'place' => 'Świetlica', 'starts_at' => '2026-12-19T10:00', 'is_active' => '1', 'copy_of' => $source->id,
        ])->assertSessionHasNoErrors();

        $copy = KreatywniWorkshop::query()->whereKeyNot($source->id)->sole();
        $this->assertNotNull($copy->imageUrl(), 'grafika z oryginału');
        $this->assertNull($copy->ends_at);
    }

    public function test_strona_kalendarza_pokazuje_nadchodzace_w_miesiacach_bez_ukrytych_i_minionych(): void
    {
        $this->useTemplate();
        // Widok „sama lista” — siatka miesiąca ma osobny test.
        $this->calendarPage(['lead' => 'Zapraszamy na warsztaty otwarte.', 'view' => 'list']);
        $with = $this->workshop('Świece sojowe', '2026-12-12 10:00', '2026-12-12 13:00', ['place' => 'Dom Kultury', 'participants' => 'do 20 osób', 'description' => 'Pierwszy.', 'image_alt' => 'Świece na stole']);
        $with->addMedia(UploadedFile::fake()->image('swiece.jpg', 800, 400))->toMediaCollection('image');
        $this->workshop('Wianki wielkanocne', '2027-03-28 11:00');
        $this->workshop('Dzisiejszy bez końca', '2026-11-23 09:00');
        $this->workshop('Miniony', '2026-11-10 10:00', '2026-11-10 12:00');
        $this->workshop('Ukryty', '2026-12-20 10:00', null, ['is_active' => false]);

        $html = $this->get('/kalendarz-warsztatow')->assertOk()->getContent();
        $this->assertStringContainsString('<h1 id="kc-page-title">Kalendarz warsztatów</h1>', $html);
        $this->assertStringContainsString('<p class="kc-lead">Zapraszamy na warsztaty otwarte.</p>', $html);
        foreach (['Listopad 2026', 'Grudzień 2026', 'Marzec 2027'] as $month) {
            $this->assertStringContainsString('class="kc-ws-month">'.$month.'</h2>', $html, $month);
        }
        $this->assertLessThan(strpos($html, 'Wianki wielkanocne'), strpos($html, 'Świece sojowe'), 'od najbliższego');
        $this->assertStringContainsString('Dzisiejszy bez końca', $html, 'bez końca widoczny do końca dnia');
        $this->assertStringContainsString('<time class="kc-ws-date" datetime="2026-12-12T09:00:00+00:00">', $html);
        $this->assertStringContainsString('<span class="kc-sr">12 grudnia 2026</span>', $html);
        $this->assertStringContainsString('alt="Świece na stole"', $html);
        $this->assertStringContainsString('<span class="kc-sr">Miejsce: </span>Dom Kultury', $html);
        $this->assertStringContainsString('<span class="kc-sr">Liczba uczestników: </span>do 20 osób', $html);
        $this->assertStringContainsString('sobota, 12 grudnia 2026, 10:00–13:00', $html);
        $this->assertStringNotContainsString('Ukryty', $html);
        $this->assertStringNotContainsString('Miniony', $html);
        $this->assertStringNotContainsString('Minione warsztaty', $html);

        Page::query()->where('slug', 'kalendarz-warsztatow')->sole()->update(['type_data' => ['show_past' => true, 'view' => 'list']]);
        $html = $this->get('/kalendarz-warsztatow')->assertOk()->getContent();
        $this->assertStringContainsString('Minione warsztaty', $html);
        $this->assertStringContainsString('<strong>Miniony</strong>', $html);
    }

    public function test_pusty_kalendarz_zaprasza_do_kontaktu(): void
    {
        $this->useTemplate();
        $this->calendarPage();

        $this->get('/kalendarz-warsztatow')->assertOk()->assertSee('Nowe terminy pojawią się wkrótce.');
    }

    public function test_typ_strony_do_wyboru_tylko_w_szablonie_kreatywni(): void
    {
        $this->useTemplate('default');
        $this->assertNotContains('kreatywni_workshops', collect(PageTypeRegistry::groups())->flatten()->all());

        $this->useTemplate();
        $this->assertContains('kreatywni_workshops', collect(PageTypeRegistry::groups())->flatten()->all());
        $this->actingAs($this->admin())->get(route('admin.podstrony.create'))->assertOk()
            ->assertSee('Kalendarz warsztatów')->assertSee('data-type-fields="kreatywni_workshops"', false);
    }

    public function test_dziala_przed_migracja_i_tylko_w_szablonie_kreatywni(): void
    {
        $this->useTemplate('default');
        $this->actingAs($this->admin())->get(route('admin.kreatywni-warsztaty.index'))->assertNotFound();

        $this->useTemplate();
        $this->calendarPage();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.kreatywni-warsztaty.index'), false);

        Schema::drop('kreatywni_workshops');
        \Illuminate\Support\Once::flush();
        PageTypeRegistry::reset();
        $this->get('/kalendarz-warsztatow')->assertOk()->assertSee('Nowe terminy pojawią się wkrótce.');
        $this->get(route('admin.kreatywni-warsztaty.index'))->assertNotFound();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.kreatywni-warsztaty.index'), false);
    }

    public function test_warsztat_innej_witryny_jest_niedostepny(): void
    {
        $this->useTemplate();
        $other = KreatywniWorkshop::withoutGlobalScopes()->create(['site_id' => 9999, 'title' => 'Obcy', 'starts_at' => now()->addDay(), 'is_active' => true]);

        $this->actingAs($this->admin())->get(route('admin.kreatywni-warsztaty.edit', $other))->assertNotFound();
    }

    public function test_modul_w_ustawieniach_wylaczony_chowa_panel_typ_i_kalendarz(): void
    {
        $site = $this->useTemplate();
        $this->calendarPage();
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()->assertSee('Warsztaty – Kreatywni (kalendarz warsztatów)');

        $site->forceFill(['disabled_modules' => ['workshops']])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        PageTypeRegistry::reset();

        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.kreatywni-warsztaty.index'), false);
        $this->get(route('admin.kreatywni-warsztaty.index'))->assertSee('Moduł dezaktywowany');
        $this->assertNotContains('kreatywni_workshops', collect(PageTypeRegistry::groups())->flatten()->all());
        auth()->logout();
        $this->get('/kalendarz-warsztatow')->assertNotFound();
    }

    public function test_dostep_dla_grupy_z_modulem_i_koordynatora(): void
    {
        $this->useTemplate();
        $withModule = \App\Models\UserGroup::create(['name' => 'Warsztaty', 'modules' => ['workshops']]);
        $pagesOnly = \App\Models\UserGroup::create(['name' => 'Strony', 'modules' => ['pages']]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR, 'user_group_id' => $withModule->id]);
        $pagesEditor = User::factory()->create(['role' => User::ROLE_EDITOR, 'user_group_id' => $pagesOnly->id]);
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);

        $this->actingAs($editor)->get(route('admin.kreatywni-warsztaty.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('admin.kreatywni-warsztaty.index'))->assertOk();
        $this->actingAs($pagesEditor)->get(route('admin.kreatywni-warsztaty.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.grupy.create'))->assertOk()->assertSee('value="workshops"', false);
    }

    public function test_widok_kalendarza_siatka_miesiaca_z_nawigacja_i_lista(): void
    {
        $this->useTemplate();
        $this->calendarPage();
        $today = $this->workshop('Dzisiejszy', '2026-11-23 15:00', '2026-11-23 17:00');
        $past = $this->workshop('Miniony', '2026-11-10 10:00', '2026-11-10 12:00');
        $dec = $this->workshop('Świece sojowe', '2026-12-12 10:00', '2026-12-12 13:00');

        // Domyślnie miesiąc najbliższego terminu (listopad), tabela z dniami, dzisiaj oznaczone, linki do kart.
        $html = $this->get('/kalendarz-warsztatow')->assertOk()->getContent();
        $this->assertStringContainsString('<table class="kc-cal" aria-labelledby="kc-cal-title">', $html);
        $this->assertStringContainsString('<h2 id="kc-cal-title">Listopad 2026</h2>', $html);
        $this->assertStringContainsString('<span class="kc-sr">poniedziałek</span>', $html);
        $this->assertStringContainsString('<td class="is-today" aria-current="date"><span class="kc-cal-day"><span aria-hidden="true">23</span><span class="kc-sr">23 listopada, dziś</span></span>', $html);
        $this->assertStringContainsString('<a href="#warsztat-'.$today->id.'" class="kc-cal-ev"><span class="kc-cal-time">15:00</span><span class="kc-cal-name">Dzisiejszy</span></a>', $html);
        $this->assertStringContainsString('<span class="kc-sr"> (odbył się)</span>', $html, 'miniony termin w siatce bez linku');
        $this->assertStringContainsString('id="warsztat-'.$today->id.'"', $html);
        $this->assertStringContainsString('href="#lista-terminow"', $html);
        $this->assertStringContainsString('<h2 class="kc-ws-month">Najbliższe terminy</h2>', $html);
        $this->assertStringContainsString('class="kc-ws-month">Grudzień 2026</h3>', $html, 'w widoku kalendarz + lista miesiące listy to H3');
        $this->assertStringContainsString('?miesiac=2026-10#kalendarz" rel="prev"', $html);
        $this->assertStringContainsString('?miesiac=2026-12#kalendarz" rel="next"', $html);

        // Inny miesiąc z adresu; zły parametr = domyślny miesiąc.
        $html = $this->get('/kalendarz-warsztatow?miesiac=2026-12')->assertOk()->getContent();
        $this->assertStringContainsString('<h2 id="kc-cal-title">Grudzień 2026</h2>', $html);
        $this->assertStringContainsString('<a href="#warsztat-'.$dec->id.'" class="kc-cal-ev">', $html);
        $this->get('/kalendarz-warsztatow?miesiac=abc')->assertOk()->assertSee('<h2 id="kc-cal-title">Listopad 2026</h2>', false);
        $this->get('/kalendarz-warsztatow?miesiac=2027-02')->assertOk()->assertSee('W tym miesiącu nie ma zaplanowanych warsztatów.');

        // Sam kalendarz: karty tylko wybranego miesiąca, bez listy najbliższych.
        Page::query()->where('slug', 'kalendarz-warsztatow')->sole()->update(['type_data' => ['view' => 'calendar']]);
        $html = $this->get('/kalendarz-warsztatow?miesiac=2026-12')->assertOk()->getContent();
        $this->assertStringContainsString('id="warsztat-'.$dec->id.'"', $html);
        $this->assertStringNotContainsString('id="warsztat-'.$today->id.'"', $html);
        $this->assertStringNotContainsString('Najbliższe terminy', $html);

        // Sama lista: bez siatki.
        Page::query()->where('slug', 'kalendarz-warsztatow')->sole()->update(['type_data' => ['view' => 'list']]);
        $this->get('/kalendarz-warsztatow')->assertOk()->assertDontSee('class="kc-cal"', false);
        $this->assertNotNull($past);
    }

    public function test_najblizsze_warsztaty_na_stronie_glownej(): void
    {
        $site = $this->useTemplate();
        $this->workshop('Pierwszy', '2026-11-28 10:00');
        $this->workshop('Drugi', '2026-12-05 10:00');
        $this->workshop('Trzeci', '2026-12-12 10:00');
        $fourth = $this->workshop('Czwarty', '2026-12-19 10:00');

        // Bez strony z kalendarzem sekcji nie ma (przycisk nie miałby dokąd prowadzić).
        $this->get('/')->assertOk()->assertDontSee('kc-ws-home-title', false);

        $calendar = $this->calendarPage();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<h2 id="kc-ws-home-title" class="kc-h2">Najbliższe warsztaty</h2>', $html);
        $this->assertSame(3, substr_count($html, 'class="kc-ws-card'), 'trzy najbliższe');
        $this->assertStringNotContainsString('Czwarty', $html);
        $this->assertStringContainsString('<a href="'.$calendar->publicUrl().'#warsztat-', $html);
        $this->assertStringContainsString('Zobacz kalendarz warsztatów', $html);
        $this->assertLessThan(strpos($html, 'kc-ws-home-title'), strpos($html, 'id="oferta"'), 'po ofercie warsztatów');
        $this->assertNotNull($fourth);

        // Przełącznik w Ustawienia → Szablon i wyłączony moduł chowają sekcję.
        $site->forceFill(['kreatywni_texts' => ['_hidden' => ['team', 'workshops']]])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/')->assertOk()->assertDontSee('kc-ws-home-title', false);
        $site->forceFill(['kreatywni_texts' => null, 'disabled_modules' => ['workshops']])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/')->assertOk()->assertDontSee('kc-ws-home-title', false);
    }
}
