<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Database\Seeders\FeerIsLayoutSeeder;
use Database\Seeders\FeerIsMenuSeeder;
use Database\Seeders\FeerIsPortalContentSeeder;
use Database\Seeders\FeerIsPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Treści i układ portalu instytucji szkoleniowo-zajęciowej (FEER IS) z seedera. */
class FeerIsPortalSeedersTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $site;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->site = SiteSetting::create([
            'site_name' => 'test2', 'slug' => 'test2', 'parent_site_id' => SiteSetting::current()->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR,
        ]);
        putenv('SITE_SLUG=test2');
    }

    protected function tearDown(): void
    {
        putenv('SITE_SLUG');
        parent::tearDown();
    }

    private function pages()
    {
        return Page::withoutGlobalScopes()->where('site_id', $this->site->id);
    }

    public function test_tworzy_szkice_stron_z_hierarchia_typami_i_ikonami_uslug(): void
    {
        $this->seed(FeerIsPortalContentSeeder::class);
        $this->seed(FeerIsPortalContentSeeder::class);

        $this->assertTrue($this->pages()->get()->every(fn ($p) => $p->is_published), 'domyślnie strony przykładowe są opublikowane (menu nie prowadzi do 404)');
        $this->assertSame(1, $this->pages()->where('title', 'Dla osób')->count(), 'bez duplikatów');

        $persons = $this->pages()->where('title', 'Dla osób')->sole();
        $services = $this->pages()->where('parent_id', $persons->id)->orderBy('order')->get();
        $this->assertSame(['Kursy i szkolenia otwarte', 'Zajęcia indywidualne', 'Warsztaty tematyczne', 'Poradnictwo i konsultacje'], $services->pluck('title')->all());
        $this->assertSame('mi-school', $services->first()->layout_data['icon']);
        $this->assertNotEmpty($services->first()->meta_description);

        $about = $this->pages()->where('type', 'training_institution')->sole();
        $this->assertSame(['Akredytacje i jakość', 'Regulamin szkoleń', 'Cennik szkoleń'], $this->pages()->where('parent_id', $about->id)->orderBy('order')->pluck('title')->all());

        $catalog = $this->pages()->where('type', 'training_catalog')->sole();
        $offers = $this->pages()->where('parent_id', $catalog->id)->where('type', 'training_offer')->get();
        $this->assertCount(3, $offers);
        $this->assertNotEmpty($offers->first()->typeData()['program']);
    }

    public function test_z_portal_draft_strony_powstaja_jako_szkice(): void
    {
        putenv('PORTAL_DRAFT=1');
        try {
            $this->seed(FeerIsPortalContentSeeder::class);
        } finally {
            putenv('PORTAL_DRAFT');
        }

        $this->assertTrue($this->pages()->get()->every(fn ($p) => ! $p->is_published));
    }

    public function test_nie_nadpisuje_zmienionej_tresci_ale_uzupelnia_pusta(): void
    {
        $own = Page::withoutGlobalScopes()->create(['site_id' => $this->site->id, 'title' => 'Dla osób', 'slug' => 'dla-osob', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Własny tekst redakcji.</p>']);
        $empty = Page::withoutGlobalScopes()->create(['site_id' => $this->site->id, 'title' => 'Trenerzy', 'slug' => 'trenerzy', 'type' => 'standard', 'is_published' => false, 'content' => '']);

        $this->seed(FeerIsPortalContentSeeder::class);

        $this->assertSame('<p>Własny tekst redakcji.</p>', $own->fresh()->content);
        $this->assertTrue((bool) $own->fresh()->is_published);
        $this->assertStringContainsString('Poznaj osoby', $empty->fresh()->content);
    }

    public function test_teksty_nie_maja_wzorcow_do_poprawy_z_humanizera(): void
    {
        $this->seed(FeerIsPortalContentSeeder::class);

        foreach ($this->pages()->get() as $page) {
            $text = html_entity_decode(strip_tags(str_replace('[podstrony]', '', (string) $page->content.' '.$page->meta_description.' '.collect(\Illuminate\Support\Arr::flatten((array) $page->type_data))->implode(' '))));
            $this->assertStringNotContainsString(' — ', $text, "myślnik jako spójnik: {$page->title}");
            $this->assertStringNotContainsString('[', $text, "nawias szablonowy: {$page->title}");
            $this->assertDoesNotMatchRegularExpression('/nie [^.]{1,60}, lecz /u', $text, "„nie X, lecz Y”: {$page->title}");
            $this->assertDoesNotMatchRegularExpression('/\d\s?(zł|PLN)/u', $text, "cena: {$page->title}");
        }
    }

    public function test_uklad_ustawia_wariant_zakladki_i_przyciski_bez_nadpisywania(): void
    {
        $this->seed(FeerIsPortalContentSeeder::class);
        $this->site->forceFill(['is_cta_label' => 'Własny przycisk'])->save();

        $this->seed(FeerIsLayoutSeeder::class);

        $site = $this->site->fresh();
        $this->assertSame('feer', $site->site_template);
        $this->assertSame('feer_is', $site->themeVariant());
        $this->assertCount(2, $site->servicesTabs());
        $this->assertSame('Dla osób', $site->servicesTabs()[0]['label']);
        $this->assertSame('Własny przycisk', $site->is_cta_label, 'wpis z panelu zostaje');
        $this->assertSame('Zobacz wszystkie szkolenia', $site->services_all_label, 'puste pola dostają wartości domyślne');
        $this->assertStringStartsWith('/', $site->is_cta_url);
    }

    public function test_cala_seria_daje_dzialajaca_strone_glowna_z_zakladkami_i_menu(): void
    {
        $this->seed(FeerIsPortalSeeder::class);
        $this->seed(FeerIsMenuSeeder::class);
        $html = $this->get('/test2')->assertOk()
            ->assertSee('Dla osób')->assertSee('Dla instytucji')->assertSee('Kursy i szkolenia otwarte')->assertSee('Szkolenia zamknięte')
            ->assertSee('Zapisz się')->assertDontSee('Materiały edukacyjne')->assertDontSee('Nasze działania')
            ->getContent();
        $this->assertStringContainsString('href="/test2/katalog-szkolen"', $html);

        $this->get('/test2/katalog-szkolen')->assertOk()->assertSee('Podstawy obsługi komputera')->assertSee('Dostępne dokumenty cyfrowe');
        $this->get('/test2/regulamin-szkolen')->assertOk()->assertSee('Zapisy')->assertSee('Uwagi i reklamacje');
    }

    public function test_trzy_przykladowe_szkolenia_w_module_zajec_z_terminami_bez_cen(): void
    {
        $this->seed(\Database\Seeders\FeerIsSampleCoursesSeeder::class);
        $this->seed(\Database\Seeders\FeerIsSampleCoursesSeeder::class);

        $courses = \App\Models\Course::withoutGlobalScopes()->where('site_id', $this->site->id)->orderBy('order')->get();
        $this->assertCount(3, $courses);
        $this->assertTrue($courses->every(fn ($c) => $c->status === 'active' && $c->price === null));
        $this->assertTrue($courses->every(fn ($c) => $c->editions()->count() >= 1));

        $this->get('/test2/zajecia')->assertOk()->assertSee('Komputer z czytnikiem ekranu')->assertSee('Dostępne dokumenty Word i PDF')->assertSee('Microsoft 365 w codziennej pracy');
        $this->get('/test2/zajecia/komputer-z-czytnikiem-ekranu')->assertOk()->assertSee('czytnika ekranu');
    }

    public function test_strona_glowna_ma_domyslnie_ukryty_blok_aktualnosci_i_mozna_go_wlaczyc(): void
    {
        \App\Models\News::withoutGlobalScopes()->create(['site_id' => $this->site->id, 'title' => 'Wpis probny', 'slug' => 'wpis-probny', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subHour()]);
        $this->seed(FeerIsPortalSeeder::class);

        $this->get('/test2')->assertOk()->assertDontSee('feer-is-news-heading', false);

        $this->site->forceFill(['homepage_sections_hidden' => []])->save();
        $this->get('/test2')->assertOk()->assertSee('feer-is-news-heading', false)->assertSee('Wpis probny');
    }

    public function test_strona_szkolenia_pokazuje_program_trenera_warunki_cennik_i_faq(): void
    {
        $this->seed(\Database\Seeders\FeerIsSampleCoursesSeeder::class);
        $trainer = \App\Models\Facilitator::create(['name' => 'Anna Testowa', 'role' => 'Trenerka dostępności', 'bio' => '<p>Prowadzi szkolenia z dostępności od lat.</p>', 'website' => 'https://example.test']);
        $course = \App\Models\Course::withoutGlobalScopes()->where('slug', 'dostepne-dokumenty-word-i-pdf')->sole();
        $course->forceFill(['facilitator_id' => $trainer->id])->save();

        $this->get('/test2/zajecia/dostepne-dokumenty-word-i-pdf')->assertOk()
            ->assertSee('Dla kogo')->assertSee('Czego się nauczysz')->assertSee('Program')->assertSee('Struktura dokumentu')
            ->assertSee('Prowadzi')->assertSee('Anna Testowa')->assertSee('Trenerka dostępności')->assertSee('Prowadzi szkolenia z dostępności')
            ->assertSee('Cennik')->assertSee('Dofinansowanie')->assertSee('Warunki udziału')->assertSee('Rezygnacja')
            ->assertSee('Najczęstsze pytania')->assertSee('listę rezerwową')->assertSee('8 godzin dydaktycznych');
    }

    public function test_panel_szkolenia_zapisuje_nowe_pola_oferty(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
        $trainer = \App\Models\Facilitator::create(['name' => 'Jan Trener']);

        $this->actingAs($admin)->withSession(['admin_site_chosen' => true, 'admin_active_site_id' => $this->site->id])
            ->post(route('admin.kursy.store'), [
                'title' => 'Nowe szkolenie', 'category' => 'wcag', 'format' => 'online', 'status' => 'active',
                'facilitator_id' => $trainer->id, 'duration' => '4 godziny', 'level' => 'podstawowy', 'funding' => 'BUR',
                'audience' => [['text' => 'Urzędnicy'], ['text' => '']], 'program' => [['title' => 'Moduł 1', 'hours' => '2', 'text' => 'Opis'], ['title' => '', 'hours' => '', 'text' => '']],
                'faq' => [['question' => 'Czy online?', 'answer' => 'Tak.']], 'terms' => '<p>Zasady.</p><script>alert(1)</script>',
            ])->assertSessionHasNoErrors();

        $course = \App\Models\Course::withoutGlobalScopes()->where('slug', 'nowe-szkolenie')->sole();
        $this->assertSame($trainer->id, $course->facilitator_id);
        $this->assertSame(['Urzędnicy'], $course->listOf('audience'));
        $this->assertCount(1, $course->programModules());
        $this->assertSame('Czy online?', $course->faqItems()[0]['question']);
        $this->assertStringNotContainsString('<script', (string) $course->terms);

        $this->get(route('admin.kursy.edit', $course))->assertOk()->assertSee('Trener (z bazy prowadzących)')->assertSee('Warunki udziału');
    }
}
