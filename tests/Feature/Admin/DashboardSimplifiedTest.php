<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\News;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Uproszczony pulpit: Na dziś, Utwórz (wg roli), Do dokończenia, Ostatnia aktywność i Najbliższe wydarzenia — w obu stylach panelu. */
class DashboardSimplifiedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function dashboard(string $role, string $scheme): string
    {
        $user = User::factory()->create(['role' => $role, 'admin_scheme' => $scheme]);

        return $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->getContent();
    }

    /** Zawartość <section aria-labelledby="$id">…</section> albo pusty napis. */
    private function section(string $html, string $id): string
    {
        return preg_match('~<section aria-labelledby="'.preg_quote($id, '~').'".*?</section>~s', $html, $m) ? $m[0] : '';
    }

    /** Adresy skrótów „Utwórz”: lista rozwijana w klasycznym pulpicie, kafle w Metro. @return array<int, string> */
    private function createLinks(string $html, bool $metro): array
    {
        $block = $metro
            ? $this->section($html, 'mt-create')
            : (preg_match('~<ul id="dash-create-menu".*?</ul>~s', $html, $m) ? $m[0] : '');
        preg_match_all('~<a href="([^"]+)"~', $block, $links);

        return $links[1];
    }

    /** @return array<string, array{0: string}> */
    public static function styles(): array
    {
        return ['klasyczny' => ['krem'], 'Metro' => ['metro']];
    }

    #[DataProvider('styles')]
    public function test_pulpit_nie_ma_usunietych_sekcji(string $scheme): void
    {
        $html = $this->dashboard(User::ROLE_ADMIN, $scheme);

        // Liczniki, kafle modułów, ankieta, ściąga o wymiarach grafik i filtry aktywności są poza pulpitem (menu boczne, Multimedia).
        foreach (['Panel sterowania', 'Zawartość serwisu', 'Aktywna ankieta', 'Zalecane wymiary grafik', 'Filtr aktywności',
            'id="dash-stats"', 'id="dash-tiles"', 'id="dash-dims"', 'id="dash-poll"', 'class="j-tile', 'mt-stats', 'mt-group-'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, "Sekcja „{$gone}” miała zniknąć z pulpitu.");
        }
        $this->assertStringContainsString('Na dziś', $html);
    }

    public function test_pulpit_nie_przekazuje_do_widoku_juz_niepotrzebnych_danych(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        // Odpowiedź przechodzi przez minifikator HTML (original to już napis), więc dane widoku łapiemy kompozytorem.
        $keys = [];
        View::composer('admin.dashboard', function ($view) use (&$keys) {
            $keys = array_keys($view->getData());
        });

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        // Brak tych danych oznacza też brak ciężkich zapytań z liczeniem treści wszystkich modułów przy każdym wejściu na pulpit.
        foreach (['stats', 'recentNews', 'recentPages', 'activePoll', 'draftCounts'] as $key) {
            $this->assertNotContains($key, $keys, "Pulpit nie potrzebuje już danych „{$key}”.");
        }
        foreach (['attention', 'shortcuts', 'activity', 'drafts', 'upcoming', 'tracksContent'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    /** @return array<string, array{0: string, 1: array<int, string>, 2: array<int, string>, 3: bool}> */
    public static function roles(): array
    {
        return [
            'administrator' => [User::ROLE_ADMIN, ['admin.newsy.create', 'admin.podstrony.create', 'admin.projekty.create', 'admin.wydarzenia.create', 'admin.kursy.create', 'admin.newsletter.kampanie.create'], [], true],
            'koordynator' => [User::ROLE_COORDINATOR, ['admin.wydarzenia.create', 'admin.kursy.create', 'admin.wolontariat.create', 'admin.praca.create'], ['admin.newsy.create', 'admin.podstrony.create', 'admin.newsletter.kampanie.create', 'admin.bip-dokumenty.create'], true],
            'redaktor newslettera' => [User::ROLE_NEWSLETTER_EDITOR, ['admin.newsletter.kampanie.create'], ['admin.newsy.create', 'admin.wydarzenia.create', 'admin.bip-dokumenty.create'], false],
            'edytor BIP' => [User::ROLE_BIP_EDITOR, ['admin.bip-dokumenty.create'], ['admin.newsy.create', 'admin.wydarzenia.create', 'admin.newsletter.kampanie.create'], false],
        ];
    }

    /**
     * @param  array<int, string>  $expected  skróty „Utwórz”, które rola ma widzieć
     * @param  array<int, string>  $forbidden  skróty spoza zakresu roli
     */
    #[DataProvider('roles')]
    public function test_skroty_utworz_zalezą_od_roli_i_sa_te_same_w_obu_stylach(string $role, array $expected, array $forbidden, bool $tracksContent): void
    {
        $classic = $this->dashboard($role, 'krem');
        $metro = $this->dashboard($role, 'metro');

        $classicLinks = $this->createLinks($classic, false);
        $metroLinks = $this->createLinks($metro, true);

        foreach ($expected as $route) {
            $this->assertContains(route($route), $classicLinks, "Klasyczny pulpit: brak skrótu {$route} dla roli {$role}.");
        }
        foreach ($forbidden as $route) {
            $this->assertNotContains(route($route), $classicLinks, "Klasyczny pulpit: skrót {$route} spoza zakresu roli {$role}.");
            $this->assertNotContains(route($route), $metroLinks, "Metro: skrót {$route} spoza zakresu roli {$role}.");
        }
        // Metro to te same skróty co klasyczna lista, w tej samej kolejności, ale najwyżej osiem kafli.
        $this->assertSame(array_slice($classicLinks, 0, 8), $metroLinks);
        $this->assertLessThanOrEqual(8, count($metroLinks));

        // Obie wersje mają „Na dziś” (sprawy do decyzji albo „Wszystko załatwione”) i te same dolne listy.
        $this->assertStringContainsString('id="dash-attention"', $classic);
        $this->assertStringContainsString('id="mt-today"', $metro);
        foreach ([$classic, $metro] as $html) {
            $this->assertSame($tracksContent, str_contains($html, 'id="dash-activity"'), "Ostatnia aktywność dla roli {$role}");
        }
    }

    public function test_metro_ma_najwyzej_osiem_kafli_utworz_a_klasyczna_lista_wszystkie(): void
    {
        $classic = $this->createLinks($this->dashboard(User::ROLE_ADMIN, 'krem'), false);
        $metro = $this->createLinks($this->dashboard(User::ROLE_ADMIN, 'metro'), true);

        $this->assertGreaterThan(8, count($classic), 'Administrator ma więcej niż osiem skrótów — lista rozwijana pokazuje wszystkie.');
        $this->assertCount(8, $metro);
    }

    #[DataProvider('styles')]
    public function test_szkice_i_aktywnosc_maja_limity_bez_filtrow(string $scheme): void
    {
        foreach (range(1, 8) as $i) {
            News::create(['title' => "Szkic {$i}", 'slug' => "szkic-{$i}", 'content' => '<p>x</p>', 'is_published' => false]);
        }
        foreach (range(1, 4) as $i) {
            News::create(['title' => "Wpis {$i}", 'slug' => "wpis-{$i}", 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDays($i)]);
        }

        $html = $this->dashboard(User::ROLE_ADMIN, $scheme);

        $drafts = $this->section($html, 'dash-drafts');
        $activity = $this->section($html, 'dash-activity');
        $this->assertSame(5, substr_count($drafts, '<li>'), 'Do dokończenia: najwyżej pięć szkiców.');
        $this->assertSame(5, substr_count($drafts, 'Dokończ<span'));
        $this->assertSame(6, substr_count($activity, '<li>'), 'Ostatnia aktywność: najwyżej sześć pozycji.');
        $this->assertStringNotContainsString('aria-pressed', $activity, 'Aktywność bez przycisków filtra.');
        $this->assertStringContainsString('Do dokończenia', $html);
        $this->assertStringContainsString('Ostatnia aktywność', $html);
    }

    #[DataProvider('styles')]
    public function test_najblizsze_wydarzenia_tylko_gdy_sa_i_najwyzej_trzy(string $scheme): void
    {
        $twoColumns = 'xl:grid-cols-[minmax(0,1fr)_22rem]';

        // Bez wydarzeń nie ma prawej kolumny, a lista aktywności zajmuje całą szerokość.
        $html = $this->dashboard(User::ROLE_ADMIN, $scheme);
        $this->assertStringNotContainsString('id="dash-events"', $html);
        $this->assertStringNotContainsString($twoColumns, $html);

        foreach (range(1, 4) as $i) {
            Event::create([
                'title' => "Spotkanie {$i}", 'slug' => "spotkanie-{$i}", 'lead' => 'Krótki opis.', 'type' => 'szkolenie', 'mode' => 'stacjonarnie',
                'starts_at' => now()->addDays($i), 'ends_at' => now()->addDays($i)->addHours(2), 'is_published' => true,
            ]);
        }

        $html = $this->dashboard(User::ROLE_ADMIN, $scheme);
        $events = $this->section($html, 'dash-events');
        $this->assertSame(3, substr_count($events, '<li>'), 'Najbliższe wydarzenia: najwyżej trzy.');
        $this->assertStringContainsString($twoColumns, $html);
    }

    #[DataProvider('styles')]
    public function test_rola_bez_tresci_nie_ma_pustych_list_na_pulpicie(string $scheme): void
    {
        $html = $this->dashboard(User::ROLE_BIP_EDITOR, $scheme);

        foreach (['id="dash-drafts"', 'id="dash-activity"', 'id="dash-events"', 'Brak treści do pokazania'] as $absent) {
            $this->assertStringNotContainsString($absent, $html, $scheme);
        }
        $this->assertStringContainsString('Wszystko załatwione', $html);
    }

    public function test_wymiary_grafik_sa_na_stronie_multimediow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.multimedia.index'))->assertOk()
            ->assertSee('Zalecane wymiary grafik')
            ->assertSee('id="media-sizes"', false)
            ->assertSee('1600×600 px')
            ->assertSee('aria-controls="media-sizes"', false);
    }
}
