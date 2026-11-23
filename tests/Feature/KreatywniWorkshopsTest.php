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
        $this->calendarPage(['lead' => 'Zapraszamy na warsztaty otwarte.']);
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

        Page::query()->where('slug', 'kalendarz-warsztatow')->sole()->update(['type_data' => ['show_past' => true]]);
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
}
