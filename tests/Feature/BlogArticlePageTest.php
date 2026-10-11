<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogTag;
use Modules\Newsletter\Models\NewsletterForm;

class BlogArticlePageTest extends BlogTestCase
{
    public function test_strona_wpisu_ma_metryczke_spis_tresci_udostepnianie_i_newsletter(): void
    {
        $body = '<h2>Pierwsza część</h2><p>' . str_repeat('słowo ', 450) . '</p><h3>Szczegóły</h3><p>Tekst</p><h2>Druga część</h2><p>Koniec</p>';
        $cat = BlogCategory::create(['name' => 'Dostępność', 'slug' => 'dostepnosc']);
        $article = $this->article('Długi wpis', [
            'body' => $body, 'category_id' => $cat->id, 'published_at' => now()->subDays(10),
            'excerpt' => 'Krótko o wpisie', 'image_path' => 'wiem-feer/x.jpg', 'image_alt' => 'Ręce na klawiaturze',
        ]);
        DB::connection('blog')->table('blog_articles')->where('id', $article->id)->update(['updated_at' => now()]);
        $article->tags()->sync(BlogTag::idsFromList('WCAG, Edukacja'));

        $response = $this->get('/wiem/dlugi-wpis')->assertOk();
        $response->assertSee('3 min czytania')
            ->assertSee('zaktualizowano')
            ->assertSee('aria-label="Spis treści"', false)
            ->assertSee('href="#pierwsza-czesc"', false)
            ->assertSee('id="pierwsza-czesc"', false)
            ->assertSee('alt="Ręce na klawiaturze"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('Udostępnij')
            ->assertSee('mailto:?subject=', false)
            ->assertSee('Kopiuj link')
            ->assertSee('aria-live="polite"', false)
            ->assertSee('facebook.com/sharer', false)
            ->assertSee('blog-article-newsletter-heading', false)
            ->assertSee('rel="alternate" type="application/rss+xml" title="Wiem FEER', false)
            ->assertSee('href="' . route('blog.index', ['tag' => 'wcag']) . '"', false)
            ->assertDontSee('googletagmanager', false);

        NewsletterForm::query()->update(['is_active' => false]);
        $this->get('/wiem/dlugi-wpis')->assertOk()->assertDontSee('blog-article-newsletter-heading', false);
    }

    public function test_dekoracyjne_zdjecie_ma_pusty_alt(): void
    {
        $this->article('Z dekoracją', ['image_path' => 'wiem-feer/d.jpg', 'image_alt' => null, 'image_decorative' => true]);

        $this->get('/wiem/z-dekoracja')->assertOk()->assertSee('d.jpg" alt=""', false);
    }

    public function test_powiazane_oraz_poprzedni_i_nastepny(): void
    {
        $cat = BlogCategory::create(['name' => 'Kat', 'slug' => 'kat']);
        $this->article('Najstarszy', ['published_at' => now()->subDays(3)]);
        $this->article('Środkowy', ['published_at' => now()->subDays(2), 'category_id' => $cat->id]);
        $this->article('Najnowszy', ['published_at' => now()->subDay(), 'category_id' => $cat->id]);

        $this->get('/wiem/srodkowy')->assertOk()
            ->assertSee('Poprzedni wpis')->assertSee('Następny wpis')
            ->assertSeeInOrder(['Poprzedni wpis', 'Najstarszy', 'Następny wpis', 'Najnowszy'])
            ->assertSee('Zobacz także');
    }

    public function test_uklad_portalu_kolumna_boczna_i_zobacz_takze(): void
    {
        $cat = BlogCategory::create(['name' => 'Prawo', 'slug' => 'prawo']);
        $main = $this->article('Główny wpis', ['category_id' => $cat->id, 'excerpt' => 'Pogrubiony wstęp', 'image_path' => 'wiem-feer/g.jpg', 'image_alt' => 'Opis']);
        foreach (range(1, 8) as $i) {
            $this->article('Inny ' . $i, ['category_id' => $cat->id, 'published_at' => now()->subDays(1 + $i)]);
        }
        $this->article('Spoza kategorii', ['published_at' => now()->subHours(2)]);

        $response = $this->get('/wiem/' . $main->slug)->assertOk();
        $html = $response->getContent();

        // Jeden H1, kategoria nad tytułem, metryczka z przyciskami A A A, zajawka, zdjęcie.
        $this->assertSame(1, substr_count($html, '<h1'));
        $response->assertSeeInOrder(['tracking-widest', 'Prawo', '<h1', 'Główny wpis', 'Rozmiar tekstu artykułu', 'Pogrubiony wstęp', 'g.jpg'], false)
            ->assertSee('role="group" aria-label="Rozmiar tekstu artykułu"', false)
            ->assertSee('feer-blog-text-size', false)
            ->assertSee('Największy tekst')
            ->assertSee('blog-portal-lead', false)
            ->assertSee('blog-portal-body prose', false)
            ->assertSee('lg:col-span-3', false)
            ->assertSee('<aside class="space-y-6 print:hidden lg:sticky lg:top-24 lg:self-start" aria-label="Kolumna boczna wpisu">', false)
            ->assertSee('Więcej w kategorii Prawo')
            ->assertSee('Wszystkie w kategorii Prawo')
            ->assertSee('Zobacz także');

        // „Więcej w kategorii": najwyżej 6 innych wpisów z tej kategorii, bez bieżącego.
        $aside = substr($html, strpos($html, 'blog-category-more-heading'), 4000);
        $aside = substr($aside, 0, strpos($aside, '</nav>'));
        $this->assertSame(6, substr_count($aside, 'fa-chevron-right'));
        $this->assertStringContainsString('Inny 1', $aside);
        $this->assertStringNotContainsString('Inny 7', $aside);
        $this->assertStringNotContainsString('Główny wpis', $aside);

        // „Zobacz także": 4 karty z nagłówkami H3; komentarze pod spodem.
        $related = substr($html, strpos($html, 'blog-related-heading'));
        $related = substr($related, 0, strpos($related, '</section>'));
        $this->assertSame(4, substr_count($related, '<h3'));
        $this->assertGreaterThan(strpos($html, 'blog-related-heading'), strpos($html, 'id="komentarze"'));
    }

    public function test_autorzy_profil_i_zapas_tekstowy(): void
    {
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak', 'role' => 'Trenerka dostępności', 'bio' => 'Uczy WCAG.',
            'links' => [['label' => 'LinkedIn', 'url' => 'https://linkedin.example/anna']], 'email' => 'anna@example.org', 'show_email' => false]);
        $this->article('Z profilem', ['author_id' => $anna->id]);
        $this->article('Stary wpis', ['author_name' => 'Jan Kowalski', 'author_bio' => 'Dawny redaktor.']);

        $this->get('/wiem/z-profilem')->assertOk()
            ->assertSee('O autorze')
            ->assertSee('Trenerka dostępności')
            ->assertSee('Uczy WCAG.')
            ->assertSee('href="' . route('blog.author', $anna) . '"', false)
            ->assertSee('https://linkedin.example/anna', false)
            ->assertDontSee('anna@example.org')
            ->assertSee('"author":[{"@type":"Person","name":"Anna Nowak","url":"' . route('blog.author', $anna) . '"', false);

        $this->get('/wiem/stary-wpis')->assertOk()->assertSee('Jan Kowalski')->assertSee('Dawny redaktor.')
            ->assertSee('"author":{"@type":"Person","name":"Jan Kowalski"}', false);
    }

    public function test_meta_seo_z_zapasem(): void
    {
        $this->article('Tytuł zwykły', ['meta_title' => 'Tytuł SEO', 'meta_description' => 'Opis SEO wpisu']);
        $this->article('Bez SEO', ['excerpt' => 'Zajawka jako opis']);

        $this->get('/wiem/tytul-zwykly')->assertSee('<title>Tytuł SEO — Wiem FEER</title>', false)->assertSee('content="Opis SEO wpisu"', false);
        $this->get('/wiem/bez-seo')->assertSee('<title>Bez SEO — Wiem FEER</title>', false)->assertSee('content="Zajawka jako opis"', false);
    }

    public function test_kanal_rss_i_mapa_strony(): void
    {
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak']);
        $this->article('Wpis w kanale', ['author_id' => $anna->id]);
        $this->article('Ukryty szkic', ['is_published' => false]);

        $this->get('/wiem/rss.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<title>Wpis w kanale</title>', false)
            ->assertSee('<dc:creator>Anna Nowak</dc:creator>', false)
            ->assertSee(route('blog.show', 'wpis-w-kanale'), false)
            ->assertDontSee('Ukryty szkic');

        $this->get('/sitemap.xml')->assertOk()->assertSee(route('blog.show', 'wpis-w-kanale'), false);
    }

    public function test_niewidoczny_wpis_daje_404(): void
    {
        $this->article('Przyszły', ['published_at' => now()->addDay()]);
        $this->get('/wiem/przyszly')->assertNotFound();
    }
}
