<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Facilitator;
use App\Models\Faq;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Strona główna wariantu FEER IS po przebudowie: slider, nagłówek i katalog szkoleń bez zmian, a wokół nich szybkie
 * wyszukiwanie, ścieżki odbiorców, kroki zapisu, prowadzący, pytania i kontakt. Bloki bez danych się nie pokazują.
 */
class FeerIsHomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $main = SiteSetting::current();
        $main->forceFill(['site_template' => 'feer'])->save();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'parent_site_id' => $main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is',
            'services_tabs' => 'Dla osób|dla-osob',
        ]);
    }

    private function course(array $extra = []): Course
    {
        $course = new Course($extra + ['title' => 'Czytnik ekranu', 'slug' => 'czytnik', 'category' => 'tyflo', 'format' => 'online', 'status' => 'active']);
        $course->forceFill(['site_id' => $this->sub->id])->save();

        return $course;
    }

    private function home(): string
    {
        return $this->get('/szkolenia')->assertOk()->getContent();
    }

    public function test_wyszukiwanie_ma_tylko_tematy_i_formy_ze_szkolen_i_prowadzi_do_katalogu(): void
    {
        $this->course();
        $this->course(['title' => 'Excel', 'slug' => 'excel', 'category' => 'm365', 'format' => 'stationary']);
        $this->course(['title' => 'Szkic', 'slug' => 'szkic', 'category' => 'programming', 'status' => 'draft']);

        $html = $this->home();

        $this->assertStringContainsString('Znajdź szkolenie dla siebie', $html);
        $this->assertStringContainsString('action="http://localhost:8000/szkolenia/zajecia"', $html);
        $this->assertStringContainsString('<legend>Temat</legend>', $html);
        $this->assertStringContainsString('<legend>Forma</legend>', $html);
        $this->assertStringContainsString('<input type="radio" name="kategoria" value="tyflo"><span>Tyfloinformatyka</span>', $html);
        $this->assertStringContainsString('<input type="radio" name="kategoria" value="m365"><span>Microsoft 365</span>', $html);
        $this->assertStringNotContainsString('value="programming"', $html, 'szkic nie tworzy filtra');
        $this->assertStringContainsString('<input type="radio" name="format" value="stationary"><span>Stacjonarnie</span>', $html);
        $this->assertStringContainsString('<input type="radio" name="kategoria" value="" checked><span>Wszystkie</span>', $html, 'domyślnie zaznaczone „Wszystkie”');
        $this->assertStringContainsString('<input type="radio" name="format" value="" checked><span>Każda</span>', $html);
        $this->assertStringContainsString('Pokaż szkolenia</button>', $html);
        $this->assertStringNotContainsString('Pokaż szkolenia (', $html, 'liczba nie może wprowadzać w błąd przy wybranych filtrach');
        $this->assertStringNotContainsString('<select', $html, 'zamiast list rozwijanych są przyciski wyboru');
    }

    public function test_pasek_wyszukiwania_znika_gdy_nie_ma_wyboru_ale_lista_terminow_zostaje(): void
    {
        $course = $this->course();
        $course->editions()->create(['starts_on' => now()->addWeek(), 'capacity' => 5, 'enrollment_status' => 'open']);

        $html = $this->home();

        $this->assertStringNotContainsString('Znajdź szkolenie dla siebie', $html, 'jedna kategoria i jedna forma to brak czego filtrować');
        $this->assertStringContainsString('Najbliższe szkolenia', $html);
    }

    public function test_karta_najblizszego_szkolenia_ma_kafelek_daty_dane_i_wolne_miejsca(): void
    {
        $course = $this->course(['duration' => '20 godzin dydaktycznych', 'price' => 0]);
        $edition = $course->editions()->create(['starts_on' => now()->addYear()->startOfYear()->addDays(14), 'ends_on' => now()->addYear()->startOfYear()->addDays(40), 'capacity' => 10, 'enrollment_status' => 'open']);

        $html = $this->home();

        $this->assertStringContainsString('class="fis-date"', $html);
        $this->assertStringContainsString('<b>15</b>', $html, 'dzień startu na kafelku');
        $this->assertStringContainsString('<small>'.now()->addYear()->year.'</small>', $html, 'rok widać, gdy start jest w przyszłym roku');
        $this->assertStringContainsString('Tyfloinformatyka', $html);
        $this->assertStringContainsString('Online · 20 godzin dydaktycznych · Bezpłatnie', $html);
        $this->assertStringContainsString('Wolne miejsca: 10 z 10', $html);
        $this->assertStringContainsString('/szkolenia/zajecia/czytnik?termin='.$edition->id.'#zapis', $html);
    }

    public function test_liczba_kolumn_kart_zalezy_od_liczby_szkolen(): void
    {
        $expected = [1 => '1', 2 => '2', 3 => '3', 4 => '2', 5 => '3', 6 => '3'];

        foreach ($expected as $count => $cols) {
            Course::withoutGlobalScopes()->where('site_id', $this->sub->id)->get()->each(fn ($c) => $c->delete());
            foreach (range(1, $count) as $i) {
                $course = $this->course(['title' => 'Szkolenie '.$i, 'slug' => 'szkolenie-'.$i]);
                $course->editions()->create(['starts_on' => now()->addDays($i), 'capacity' => 5, 'enrollment_status' => 'open']);
            }

            $this->assertStringContainsString('class="fis-up-grid" data-cols="'.$cols.'"', $this->home(), $count.' kart: '.$cols.' kolumn(y)');
        }
    }

    public function test_bez_szkolen_nie_ma_wyszukiwania_krokow_ani_prowadzacych(): void
    {
        $html = $this->home();

        $this->assertStringNotContainsString('Znajdź szkolenie dla siebie', $html);
        $this->assertStringNotContainsString('Jak się zapisać', $html);
        $this->assertStringNotContainsString('Kto prowadzi zajęcia', $html);
    }

    public function test_kroki_zapisu_zaleza_od_tego_czy_zapisy_online_sa_wlaczone(): void
    {
        $this->course();

        $this->sub->forceFill(['courses_registration_enabled' => false])->save();
        $off = $this->home();
        $this->assertStringContainsString('Jak się zapisać', $off);
        $this->assertStringContainsString('Skontaktuj się z nami', $off);
        $this->assertStringNotContainsString('Zapisz się na termin', $off);

        $this->sub->forceFill(['courses_registration_enabled' => true])->save();
        $on = $this->home();
        $this->assertStringContainsString('Zapisz się na termin', $on);
        $this->assertStringContainsString('listę rezerwową', $on);
        $this->assertStringNotContainsString('Skontaktuj się z nami', $on);
    }

    public function test_prowadzacy_z_widocznymi_szkoleniami_maja_linki_do_nich(): void
    {
        $anna = Facilitator::create(['name' => 'Anna Testowa', 'role' => 'Trenerka dostępności']);
        Facilitator::create(['name' => 'Bez Szkolenia']);
        $this->course(['facilitator_id' => $anna->id]);

        $html = $this->home();

        $this->assertStringContainsString('Kto prowadzi zajęcia', $html);
        $this->assertStringContainsString('Anna Testowa', $html);
        $this->assertStringContainsString('Trenerka dostępności', $html);
        $this->assertStringContainsString('/szkolenia/zajecia/czytnik', $html);
        $this->assertStringNotContainsString('Bez Szkolenia', $html);
    }

    public function test_pytania_i_kontakt_pokazuja_dane_a_bez_nich_blok_znika(): void
    {
        // Bez pytań i bez jakichkolwiek danych kontaktowych blok znika (domyślne dane kontaktowe czyścimy).
        $this->sub->forceFill(['contact_phone' => '', 'contact_email' => '', 'contact_address' => '', 'contact_city' => '', 'contact_office_address' => '', 'contact_office_city' => ''])->save();
        $empty = $this->home();
        $this->assertStringNotContainsString('Najczęstsze pytania', $empty);
        $this->assertStringNotContainsString('Masz pytania?', $empty);

        foreach (range(1, 6) as $i) {
            Faq::create(['question' => 'Pytanie numer '.$i, 'answer' => 'Odpowiedź '.$i, 'is_published' => true, 'order' => $i]);
        }
        Faq::create(['question' => 'Ukryte pytanie', 'answer' => 'x', 'is_published' => false, 'order' => 0]);
        $this->sub->forceFill(['contact_phone' => '12 616 7555', 'contact_email' => 'biuro@example.com', 'contact_office_hours' => 'pn–pt 8–16'])->save();

        $html = $this->home();

        $this->assertStringContainsString('Najczęstsze pytania', $html);
        $this->assertStringContainsString('Pytanie numer 1', $html);
        $this->assertStringContainsString('Pytanie numer 5', $html);
        $this->assertStringNotContainsString('Pytanie numer 6', $html, 'na głównej pięć pierwszych pytań');
        $this->assertStringNotContainsString('Ukryte pytanie', $html);
        $this->assertStringContainsString('Masz pytania?', $html);
        $this->assertStringContainsString('href="tel:126167555"', $html);
        $this->assertStringContainsString('biuro@example.com', $html);
        $this->assertStringContainsString('pn–pt 8–16', $html);
    }

    public function test_sekcje_sa_w_kolejnosci_a_katalog_zostaje_miedzy_sciezkami_a_krokami(): void
    {
        $page = Page::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'title' => 'Dla osób', 'slug' => 'dla-osob', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>']);
        Page::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'title' => 'Porada', 'slug' => 'porada', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>', 'parent_id' => $page->id]);
        $course = $this->course();
        $course->editions()->create(['starts_on' => now()->addWeek(), 'capacity' => 5, 'enrollment_status' => 'open']);
        $this->course(['title' => 'Excel', 'slug' => 'excel', 'category' => 'm365', 'format' => 'stationary']);

        $html = $this->home();
        $pos = fn (string $needle) => strpos($html, $needle);

        $this->assertNotFalse($pos('Znajdź szkolenie dla siebie'));
        $this->assertLessThan($pos('Wybierz swoją ścieżkę'), $pos('Znajdź szkolenie dla siebie'));
        $this->assertLessThan($pos('Najbliższe szkolenia'), $pos('Wybierz swoją ścieżkę'), 'katalog szkoleń jest pod ścieżkami odbiorców');
        $this->assertLessThan($pos('Jak się zapisać'), $pos('Najbliższe szkolenia'));
    }

    public function test_slider_i_naglowek_zostaja(): void
    {
        $html = $this->home();

        $this->assertStringContainsString('site-header', $html);
        $this->assertStringContainsString('<h1 class="sr-only">Szkolenia</h1>', $html);
    }
}
