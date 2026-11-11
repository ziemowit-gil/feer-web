<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\NavItem;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Szablon „bu2”: nagłówek z mega menu z Menu nawigacji, menu boczne i okruszki z tego samego drzewa, kafle podstron,
 * aktualność i kontakt jak podstrona, projekty w dwóch grupach, strona główna (slider, aktualności, kontakt), stopka,
 * rozmiar pliku w bloku „plik”.
 */
class TemplateBu2Test extends TestCase
{
    use RefreshDatabase;

    private function useTemplate(string $template, array $extra = []): void
    {
        $reset = fn () => \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $reset();
        SiteSetting::current()->forceFill(['site_template' => $template] + $extra)->save();
        $reset();
    }

    private function page(string $title, string $slug, array $extra = []): Page
    {
        return Page::create($extra + ['title' => $title, 'slug' => $slug, 'type' => 'standard', 'is_published' => true]);
    }

    /** Menu: „Dla użytkowników” (link do strony z podstronami) i „Kontakt”. */
    private function menu(): array
    {
        $users = $this->page('Dla użytkowników', 'dla-uzytkownikow');
        $staff = $this->page('Dla pracowników', 'dla-pracownikow', ['parent_id' => $users->id, 'order' => 1]);
        $mail = $this->page('Konta pocztowe', 'konta-pocztowe', ['parent_id' => $staff->id, 'order' => 1, 'content' => '<p>Treść o poczcie</p>']);
        $this->page('Dla studentów', 'dla-studentow', ['parent_id' => $users->id, 'order' => 2, 'layout_data' => ['icon' => 'mi-school']]);
        NavItem::create(['label' => 'Dla użytkowników', 'url' => '/dla-uzytkownikow', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Kontakt', 'url' => '/kontakt', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 2]);

        return compact('users', 'staff', 'mail');
    }

    public function test_szablon_jest_w_ustawieniach(): void
    {
        $this->assertArrayHasKey('bu2', SiteSetting::SITE_TEMPLATES);
        $this->assertArrayHasKey('bu2', SiteSetting::SITE_TEMPLATE_BRIEF);
    }

    public function test_naglowek_ma_mega_menu_z_menu_nawigacji(): void
    {
        $this->useTemplate('bu2');
        $this->menu();

        $this->get('/')->assertOk()
            ->assertSee('template-bu2', false)
            ->assertSee('class="bu2 bu2-header"', false)
            ->assertSee('aria-controls="bu2-mega-', false)
            ->assertSeeInOrder(['Dla użytkowników', 'Dla pracowników', 'Dla studentów', 'Przejdź do strony „Dla użytkowników”'], false)
            ->assertSee('aria-controls="a11y-panel"', false)
            ->assertSee('Wyszukaj');
    }

    public function test_podstrona_ma_menu_boczne_z_otwarta_galezia_i_okruszki_z_menu(): void
    {
        $this->useTemplate('bu2');
        $this->menu();

        $res = $this->get('/konta-pocztowe')->assertOk()
            ->assertSee('class="bu2-side"', false)
            ->assertSee('<h1 class="bu2-title">Konta pocztowe</h1>', false)
            ->assertSee('Treść o poczcie')
            ->assertSee('Drukuj');

        $html = $res->getContent();
        // Bieżąca strona w menu bocznym i w okruszkach.
        $this->assertMatchesRegularExpression('~<a href="[^"]*/konta-pocztowe"[^>]*aria-current="page"\s*>Konta pocztowe</a>~', $html);
        $this->assertMatchesRegularExpression('~bu2-crumbs.*Strona główna.*Dla użytkowników.*Dla pracowników.*<span aria-current="page">Konta pocztowe</span>~s', $html);
        // Gałąź bieżącej strony rozwinięta, przycisk rozwijania z aria-expanded.
        $this->assertStringContainsString('x-data="{ expanded: true }"', $html);
        $this->assertStringContainsString('aria-expanded="true"', $html);
    }

    public function test_strona_dzialu_pokazuje_kafle_podstron_z_ikonami(): void
    {
        $this->useTemplate('bu2');
        $this->menu();

        $this->get('/dla-uzytkownikow')->assertOk()
            ->assertSee('class="bu2-tiles"', false)
            ->assertSeeInOrder(['bu2-tiles', 'Dla pracowników', 'Dla studentów'], false)
            ->assertSee('>school</span>', false)
            ->assertSee('>description</span>', false);
    }

    public function test_strona_spoza_menu_otwiera_galaz_strony_nadrzednej(): void
    {
        $this->useTemplate('bu2');
        ['mail' => $mail] = $this->menu();
        $this->page('Ukryta podstrona', 'ukryta-podstrona', ['parent_id' => $mail->id]);
        // Podstrony głębiej niż drzewo z menu też są w drzewie (strona → podstrony), więc sprawdzamy stronę bez rodzica w menu.
        $this->page('Luźna strona', 'luzna-strona');

        $this->get('/luzna-strona')->assertOk()->assertSee('<h1 class="bu2-title">Luźna strona</h1>', false)
            ->assertSee('<span aria-current="page">Luźna strona</span>', false)
            ->assertDontSee('x-data="{ expanded: true }"', false);
    }

    public function test_aktualnosc_wyglada_jak_podstrona_z_data(): void
    {
        $this->useTemplate('bu2');
        $news = News::create(['title' => 'Aktualizacja serwera poczty', 'slug' => 'aktualizacja', 'is_published' => true, 'published_at' => now()->setDate(2026, 10, 27), 'content' => '<p>Prace serwisowe</p>']);

        $this->get(route('news.show', $news))->assertOk()
            ->assertSee('<h1 class="bu2-title">Aktualizacja serwera poczty</h1>', false)
            ->assertSee('Prace serwisowe')
            ->assertSee('Opublikowano:')
            ->assertSee('27 października 2026')
            ->assertSee('Drukuj');
    }

    public function test_kontakt_ma_dane_teleadresowe_i_tresc_strony(): void
    {
        $this->useTemplate('bu2', ['contact_address' => 'ul. Podgórna 50a', 'contact_city' => '65-246 Zielona Góra', 'contact_phone' => '+48 789 441 668; (68) 328 2525', 'contact_email' => 'sekretariat@example.org']);
        $this->page('Kontakt z nami', 'kontakt-z-nami', ['type' => 'contact', 'content' => '<table><tr><td>Sekretariat</td><td>+48 68 <strong>328 2525</strong></td></tr></table>']);

        $this->get('/kontakt-z-nami')->assertOk()
            ->assertSee('<h1 class="bu2-title">Kontakt z nami</h1>', false)
            ->assertSee('Dane teleadresowe')
            ->assertSee('ul. Podgórna 50a')
            ->assertSee('href="tel:+48789441668"', false)
            ->assertSee('href="tel:683282525"', false)
            ->assertSee('mailto:sekretariat@example.org', false)
            ->assertSee('<td>Sekretariat</td>', false);
    }

    public function test_projekty_w_dwoch_grupach(): void
    {
        $this->useTemplate('bu2');
        $cat = Category::create(['name' => 'Projekty', 'slug' => 'projekty']);
        Project::create(['category_id' => $cat->id, 'title' => 'PUCHACZ', 'slug' => 'puchacz', 'is_published' => true, 'status' => 'active']);
        Project::create(['category_id' => $cat->id, 'title' => 'NewMAN', 'slug' => 'newman', 'is_published' => true, 'is_completed' => true, 'status' => 'completed']);
        Project::create(['category_id' => $cat->id, 'title' => 'Szkic projektu', 'slug' => 'szkic', 'is_published' => false]);

        $this->get(route('projects.index'))->assertOk()
            ->assertSeeInOrder(['Projekty obecnie realizowane lub kontynuowane', 'PUCHACZ', 'Projekty zrealizowane', 'NewMAN'])
            ->assertDontSee('Szkic projektu');
    }

    public function test_strona_glowna_slider_aktualnosci_i_kontakt(): void
    {
        $this->useTemplate('bu2', ['contact_address' => 'ul. Podgórna 50a', 'contact_city' => 'Zielona Góra', 'contact_email' => 'ck@example.org']);
        HeroSlide::create(['title' => 'Doświadczony zespół', 'text' => 'Specjaliści wysokiej klasy', 'button_url' => '/o-nas', 'order' => 1]);
        HeroSlide::create(['title' => 'Drugi slajd', 'order' => 2]);
        News::create(['title' => 'Nowa infrastruktura danych', 'slug' => 'kmd', 'is_published' => true, 'published_at' => now()->setDate(2026, 11, 5)]);

        $this->get('/')->assertOk()
            ->assertSee('class="bu2-slider"', false)
            ->assertSee('Doświadczony zespół')
            ->assertSee('Wstrzymaj przewijanie slajdów')
            ->assertSee('Więcej<span class="bu2-sr">: Doświadczony zespół</span>', false)
            ->assertSee('Nowa infrastruktura danych')
            ->assertSee('<b>05</b><span>LIS</span>', false)
            ->assertSee('więcej aktualności')
            ->assertSee('id="bu2-contact-h"', false)
            ->assertSee('ck@example.org')
            ->assertSee('openstreetmap.org/search', false);
    }

    public function test_stopka_ma_linki_i_logotypy_finansowania(): void
    {
        $this->useTemplate('bu2');
        NavItem::create(['label' => 'Polityka prywatności', 'url' => '/polityka-prywatnosci', 'type' => 'link', 'location' => 'footer', 'is_active' => true, 'order' => 1]);

        $this->get('/')->assertOk()
            ->assertSee('class="bu2 bu2-footer"', false)
            ->assertSeeInOrder(['Mapa serwisu', 'Polityka prywatności', 'Deklaracja dostępności'], false);
    }

    public function test_blok_pliku_pokazuje_rozmiar_pliku_z_biblioteki(): void
    {
        $dir = storage_path('app/public/bu2-test');
        @mkdir($dir, 0777, true);
        file_put_contents($dir.'/regulamin.pdf', str_repeat('x', 2048));

        try {
            $this->assertSame('2 KB', BlockRenderer::localFileSize('/storage/bu2-test/regulamin.pdf'));
            $this->assertSame('2 KB', BlockRenderer::localFileSize(url('/storage/bu2-test/regulamin.pdf')));
            $this->assertNull(BlockRenderer::localFileSize('https://example.com/storage/bu2-test/regulamin.pdf'));
            $this->assertNull(BlockRenderer::localFileSize('/storage/../.env'));
            $this->assertNull(BlockRenderer::localFileSize('/storage/bu2-test/brak.pdf'));
        } finally {
            @unlink($dir.'/regulamin.pdf');
            @rmdir($dir);
        }
    }

    public function test_inne_szablony_bez_stylow_bu2(): void
    {
        $this->useTemplate('default');
        $this->menu();

        $this->get('/konta-pocztowe')->assertOk()->assertDontSee('template-bu2', false)->assertDontSee('bu2-side', false)->assertDontSee('bu2-header', false);
    }
}
