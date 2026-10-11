<?php

namespace Tests\Feature;

use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogTag;
use Modules\Newsletter\Models\NewsletterForm;

class BlogIndexTest extends BlogTestCase
{
    public function test_lista_ma_uklad_portalu_z_wyroznionym_wpisem_siatka_i_kolumna_boczna(): void
    {
        $cat = BlogCategory::create(['name' => 'Dostępność', 'slug' => 'dostepnosc']);
        $this->article('Najnowszy zwykły', ['published_at' => now()->subHour(), 'category_id' => $cat->id]);
        $this->article('Wyróżniony starszy', ['published_at' => now()->subDays(5), 'is_featured' => true, 'excerpt' => 'Zajawka wyróżnionego']);
        $tagged = $this->article('Z tagiem', ['published_at' => now()->subDays(2)]);
        $tagged->tags()->sync(BlogTag::idsFromList('WCAG'));

        $response = $this->get('/wiem')->assertOk();
        $response->assertSee('Wyróżniony wpis')
            ->assertSee('Najnowsze wpisy')
            ->assertSee('aria-label="Kolumna boczna Wiem FEER"', false)
            ->assertSee('Szukaj we wpisach')
            ->assertSee('id="blog-search-mobile"', false)
            ->assertSee('Kategorie')
            ->assertSee('Popularne tagi')
            ->assertSee('Pełna treść');
        // Wyróżniony (is_featured) idzie przed najnowszym, mimo starszej daty.
        $response->assertSeeInOrder(['Wyróżniony starszy', 'Zajawka wyróżnionego', 'Najnowszy zwykły']);
        // Płasko: bez plakietek.
        $response->assertDontSee('rounded-full bg-white/15', false);
    }

    public function test_panel_newslettera_jest_zalezny_od_formularza(): void
    {
        $this->article('Wpis');

        $this->get('/wiem')->assertOk()->assertSee('blog-newsletter-heading', false)->assertSee('fa-paper-plane', false);

        NewsletterForm::query()->update(['is_active' => false]);
        $this->get('/wiem')->assertOk()->assertDontSee('blog-newsletter-heading', false);
    }

    public function test_wyszukiwanie_filtry_i_wyczysc_filtr(): void
    {
        $cat = BlogCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja']);
        $this->article('Lekcja o kontrastach', ['category_id' => $cat->id]);
        $other = $this->article('Coś innego', ['body' => '<p>Bez słowa kluczowego.</p>']);
        $other->tags()->sync(BlogTag::idsFromList('Empatia'));

        $this->get('/wiem?q=kontrast')->assertOk()
            ->assertSee('Wyniki dla „kontrast”', false)
            ->assertSee('Wyczyść filtr')
            ->assertSee('Lekcja o kontrastach')
            ->assertDontSee('Coś innego')
            ->assertDontSee('Wyróżniony wpis');

        $this->get('/wiem?kategoria=edukacja')->assertOk()
            ->assertSee('kategoria: Edukacja')
            ->assertSee('Lekcja o kontrastach')
            ->assertDontSee('Coś innego')
            ->assertSee('aria-current="page"', false);

        $this->get('/wiem?tag=empatia')->assertOk()->assertSee('Coś innego')->assertDontSee('Lekcja o kontrastach');
        $this->get('/wiem?kategoria=nie-ma')->assertNotFound();
    }

    public function test_filtr_autora(): void
    {
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak']);
        $this->article('Tekst Anny', ['author_id' => $anna->id]);
        $this->article('Tekst kogoś');

        $this->get('/wiem?autor=anna-nowak')->assertOk()->assertSee('autor: Anna Nowak')
            ->assertSee('Tekst Anny')->assertDontSee('Tekst kogoś');
    }

    public function test_paginacja_po_12_z_aria_current(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->article('Wpis numer ' . $i, ['published_at' => now()->subDays($i)]);
        }

        // 1 wyróżniony + 12 w siatce na stronie 1, reszta (2) na stronie 2.
        $page1 = $this->get('/wiem')->assertOk();
        $page1->assertSee('aria-label="Stronicowanie wpisów"', false)
            ->assertSee('aria-current="page"><span class="sr-only">Strona </span>1', false)
            ->assertSee('Wpis numer 13')
            ->assertDontSee('Wpis numer 14<', false);

        $this->get('/wiem?page=2')->assertOk()
            ->assertSee('Wpis numer 14')
            ->assertSee('Wpis numer 15')
            ->assertDontSee('Wyróżniony wpis');
    }

    public function test_wylaczone_i_zaplanowane_nie_trafiaja_na_liste(): void
    {
        $this->article('Widoczny');
        $this->article('Wyłączony wpis', ['is_disabled' => true]);
        $this->article('Zaplanowany wpis', ['published_at' => now()->addDay()]);
        $this->article('Szkic wpisu', ['is_published' => false]);

        $this->get('/wiem')->assertOk()->assertSee('Widoczny')
            ->assertDontSee('Wyłączony wpis')->assertDontSee('Zaplanowany wpis')->assertDontSee('Szkic wpisu');
    }
}
