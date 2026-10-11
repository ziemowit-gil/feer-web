<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Blog\Models\BlogArticle;

/** Licznik wyświetleń (raz na sesję, bez redaktorów) i sekcje „Najpopularniejsze”. */
class BlogPopularTest extends BlogTestCase
{
    private function setViews(BlogArticle $article, int $views): void
    {
        DB::connection('blog')->table('blog_articles')->where('id', $article->id)->update(['views' => $views]);
    }

    public function test_wyswietlenie_liczone_raz_na_sesje(): void
    {
        $article = $this->article('Czytany wpis');
        $updatedAt = $article->fresh()->updated_at;

        $this->get('/wiem/czytany-wpis')->assertOk();
        $this->get('/wiem/czytany-wpis')->assertOk();
        $this->assertSame(1, $article->fresh()->views);
        $this->assertEquals([$article->id], session(BlogController::VIEWED_SESSION_KEY));
        // Wyświetlenie nie zmienia daty ostatniej edycji.
        $this->assertEquals($updatedAt, $article->fresh()->updated_at);

        // Nowa sesja = kolejne wyświetlenie.
        $this->flushSession();
        $this->get('/wiem/czytany-wpis')->assertOk();
        $this->assertSame(2, $article->fresh()->views);
    }

    public function test_zalogowany_redaktor_i_podglad_nie_sa_liczeni(): void
    {
        $article = $this->article('Wpis redakcji');
        $draft = $this->article('Szkic', ['is_published' => false]);

        $this->actingAs($this->admin())->get('/wiem/wpis-redakcji')->assertOk();
        $this->assertSame(0, $article->fresh()->views);

        auth()->logout();
        $this->get($draft->previewUrl())->assertOk();
        $this->assertSame(0, $draft->fresh()->views);
    }

    public function test_najpopularniejsze_w_kolumnie_bocznej(): void
    {
        $a = $this->article('Hit numer jeden');
        $b = $this->article('Drugi w rankingu');
        $this->article('Bez wyświetleń');
        $hidden = $this->article('Wyłączony hit', ['is_disabled' => true]);
        $this->setViews($a, 50);
        $this->setViews($b, 10);
        $this->setViews($hidden, 999);

        $response = $this->get('/wiem')->assertOk()
            ->assertSee('id="blog-popular-heading"', false)
            ->assertSee('Najpopularniejsze');
        $html = $response->getContent();
        $section = substr($html, strpos($html, 'blog-popular-heading'));
        $section = substr($section, 0, strpos($section, '</section>'));
        $this->assertStringContainsString('Hit numer jeden', $section);
        $this->assertStringNotContainsString('Wyłączony hit', $section);
        $this->assertStringNotContainsString('Bez wyświetleń', $section);
        $this->assertLessThan(strpos($section, 'Drugi w rankingu'), strpos($section, 'Hit numer jeden'));
    }

    public function test_bez_wyswietlen_nie_ma_sekcji(): void
    {
        $this->article('Wpis');

        $this->get('/wiem')->assertOk()->assertDontSee('blog-popular-heading', false);
    }

    public function test_kolumna_wyswietlen_w_panelu_jest_sortowalna(): void
    {
        $low = $this->article('Mało czytany');
        $high = $this->article('Bardzo czytany');
        $this->setViews($low, 3);
        $this->setViews($high, 1234);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.wiem-feer.index'))->assertOk()
            ->assertSee('Wyświetlenia')
            ->assertSee('1 234');

        $this->actingAs($admin)->get(route('admin.wiem-feer.index', ['sort' => 'wyswietlenia', 'dir' => 'desc']))->assertOk()
            ->assertSee('aria-sort="descending"', false)
            ->assertSeeInOrder(['Bardzo czytany', 'Mało czytany']);

        $this->actingAs($admin)->get(route('admin.wiem-feer.index', ['sort' => 'wyswietlenia', 'dir' => 'asc']))->assertOk()
            ->assertSeeInOrder(['Mało czytany', 'Bardzo czytany']);
    }
}
