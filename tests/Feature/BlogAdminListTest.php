<?php

namespace Tests\Feature;

use App\Models\User;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogComment;
use Modules\Blog\Models\BlogTag;

/** Lista artykułów w panelu: wyszukiwanie, filtry, sortowanie, stronicowanie, akcje zbiorcze. */
class BlogAdminListTest extends BlogTestCase
{
    private function list(array $params = [])
    {
        return $this->actingAs($this->admin())->get(route('admin.wiem-feer.index', $params))->assertOk();
    }

    public function test_wyszukiwanie_po_tytule_i_tresci(): void
    {
        $this->article('Dostępne PDF-y');
        $this->article('Inny wpis', ['body' => '<p>O czytnikach ekranu</p>']);
        $this->article('Trzeci');

        $this->list(['q' => 'pdf'])->assertSee('Dostępne PDF-y')->assertDontSee('Inny wpis')->assertSee('wyszukiwanie „pdf”', false);
        $this->list(['q' => 'czytnikach'])->assertSee('Inny wpis')->assertDontSee('Trzeci');
    }

    public function test_filtr_statusu_w_tym_w_przygotowaniu(): void
    {
        $this->article('Zwykły wpis');
        $this->article('Robota trwa', ['wip_mode' => 'notice']);

        $this->list(['status' => 'wip'])->assertSee('Robota trwa')->assertDontSee('>Zwykły wpis<', false)->assertSee('W przygotowaniu');
    }

    public function test_filtr_kategorii_autora_tagu_i_formatu(): void
    {
        $cat = BlogCategory::create(['name' => 'Prawo', 'slug' => 'prawo']);
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak']);
        $jan = BlogAuthor::create(['name' => 'Jan Kowal', 'slug' => 'jan-kowal']);

        $a = $this->article('W kategorii', ['category_id' => $cat->id, 'author_id' => $anna->id]);
        $b = $this->article('Bez kategorii wpis', ['author_id' => $jan->id, 'body_format' => 'markdown', 'body_markdown' => 'x']);
        $b->coAuthors()->sync([$anna->id => ['position' => 0]]);
        $a->tags()->sync(BlogTag::idsFromList('WCAG'));

        $this->list(['kategoria' => 'prawo'])->assertSee('W kategorii')->assertDontSee('Bez kategorii wpis')->assertSee('kategoria Prawo');
        $this->list(['kategoria' => $cat->id])->assertSee('W kategorii')->assertDontSee('Bez kategorii wpis');
        $this->list(['kategoria' => 'brak'])->assertSee('Bez kategorii wpis')->assertDontSee('>W kategorii<', false);

        // Autor obejmuje także współautorstwo.
        $this->list(['autor' => $anna->id])->assertSee('W kategorii')->assertSee('Bez kategorii wpis');
        $this->list(['autor' => $jan->id])->assertSee('Bez kategorii wpis')->assertDontSee('>W kategorii<', false);

        $this->list(['tag' => 'wcag'])->assertSee('W kategorii')->assertDontSee('Bez kategorii wpis');
        $this->list(['format' => 'markdown'])->assertSee('Bez kategorii wpis')->assertDontSee('>W kategorii<', false);
        $this->list(['format' => 'html'])->assertSee('W kategorii')->assertDontSee('Bez kategorii wpis');
    }

    public function test_zakres_dat_i_przelaczniki(): void
    {
        $this->article('Stary', ['published_at' => '2026-01-10 10:00:00', 'image_path' => 'wiem-feer/a.jpg', 'image_alt' => 'Opis', 'meta_description' => 'Opis SEO']);
        $this->article('Nowy', ['published_at' => '2026-06-10 10:00:00']);
        $c = $this->article('Z komentarzem', ['published_at' => '2026-03-01 10:00:00', 'image_path' => 'wiem-feer/b.jpg', 'image_alt' => null, 'meta_description' => 'Opis']);
        BlogComment::create(['article_id' => $c->id, 'author_name' => 'Ola', 'body' => 'Hej', 'is_approved' => false]);

        $this->list(['od' => '2026-02-01', 'do' => '2026-04-01'])->assertSee('Z komentarzem')->assertDontSee('>Stary<', false)->assertDontSee('>Nowy<', false);
        $this->list(['od' => 'zła-data'])->assertSee('Stary')->assertSee('Nowy');

        $this->list(['bez_zdjecia' => 1])->assertSee('Nowy')->assertSee('Z komentarzem')->assertDontSee('>Stary<', false);
        $this->list(['bez_seo' => 1])->assertSee('Nowy')->assertDontSee('>Stary<', false)->assertDontSee('>Z komentarzem<', false);
        $this->list(['do_moderacji' => 1])->assertSee('Z komentarzem')->assertSee('1 do moderacji')->assertDontSee('>Nowy<', false);
    }

    public function test_sortowanie_z_aria_sort(): void
    {
        $this->article('Bravo', ['published_at' => now()->subDays(1)]);
        $this->article('Alfa', ['published_at' => now()->subDays(3)]);
        $this->article('Charlie', ['published_at' => now()->subDays(2)]);

        $this->list(['sort' => 'tytul', 'dir' => 'asc'])
            ->assertSeeInOrder(['>Alfa<', '>Bravo<', '>Charlie<'], false)
            ->assertSee('aria-sort="ascending"', false);
        $this->list(['sort' => 'tytul', 'dir' => 'desc'])->assertSeeInOrder(['>Charlie<', '>Bravo<', '>Alfa<'], false)->assertSee('aria-sort="descending"', false);
        $this->list(['sort' => 'publikacja', 'dir' => 'asc'])->assertSeeInOrder(['>Alfa<', '>Charlie<', '>Bravo<'], false);
        // Nieznana kolumna — bez aria-sort.
        $this->list(['sort' => 'xyz'])->assertDontSee('aria-sort=', false);
    }

    public function test_sortowanie_po_autorze_kategorii_i_komentarzach(): void
    {
        $z = BlogAuthor::create(['name' => 'Zofia', 'slug' => 'zofia']);
        $b = BlogAuthor::create(['name' => 'Barbara', 'slug' => 'barbara']);
        $k1 = BlogCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja']);
        $k2 = BlogCategory::create(['name' => 'Architektura', 'slug' => 'architektura']);
        $one = $this->article('Pierwszy', ['author_id' => $z->id, 'category_id' => $k1->id]);
        $this->article('Drugi', ['author_id' => $b->id, 'category_id' => $k2->id]);
        BlogComment::create(['article_id' => $one->id, 'author_name' => 'A', 'body' => 'x', 'is_approved' => true]);

        $this->list(['sort' => 'autor', 'dir' => 'asc'])->assertSeeInOrder(['>Drugi<', '>Pierwszy<'], false);
        $this->list(['sort' => 'kategoria', 'dir' => 'asc'])->assertSeeInOrder(['>Drugi<', '>Pierwszy<'], false);
        $this->list(['sort' => 'komentarze', 'dir' => 'desc'])->assertSeeInOrder(['>Pierwszy<', '>Drugi<'], false);
    }

    public function test_filtry_lacza_sie_a_stronicowanie_zachowuje_parametry(): void
    {
        $cat = BlogCategory::create(['name' => 'Prawo', 'slug' => 'prawo']);
        foreach (range(1, 30) as $i) {
            $this->article('Prawny ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), ['category_id' => $cat->id, 'is_published' => $i % 2 === 0]);
        }
        $this->article('Prawny poza kategorią');

        $response = $this->list(['q' => 'Prawny', 'kategoria' => 'prawo', 'status' => 'draft', 'sort' => 'tytul', 'dir' => 'asc']);
        $response->assertSee('Znaleziono: 15 artykułów.')->assertDontSee('Prawny 02')->assertDontSee('Prawny poza kategorią');

        $all = $this->list(['kategoria' => 'prawo', 'sort' => 'tytul', 'dir' => 'asc']);
        $all->assertSee('Znaleziono: 30 artykułów.')->assertSee('Prawny 25')->assertDontSee('Prawny 26');
        $html = html_entity_decode($all->getContent());
        $this->assertMatchesRegularExpression('/href="[^"]*\?(?=[^"]*kategoria=' . $cat->id . ')(?=[^"]*sort=tytul)(?=[^"]*dir=asc)[^"]*page=2"/', $html);

        $this->list(['kategoria' => 'prawo', 'sort' => 'tytul', 'dir' => 'asc', 'page' => 2])->assertSee('Prawny 26')->assertDontSee('>Prawny 01<', false);
        $this->list(['kategoria' => 'prawo', 'na_strone' => 50])->assertSee('Prawny 01')->assertSee('Prawny 30');
    }

    public function test_tabela_ma_podpis_region_i_zaznacz_wszystkie(): void
    {
        $this->article('Jakiś wpis');

        $this->list()
            ->assertSee('<caption id="articles-caption" class="sr-only">', false)
            ->assertSee('role="region" aria-labelledby="articles-caption" tabindex="0"', false)
            ->assertSee('aria-label="Zaznacz wszystkie artykuły na tej stronie"', false)
            ->assertSee('aria-label="Usuń: Jakiś wpis"', false)
            ->assertSee('role="status" aria-live="polite"', false)
            ->assertSee('Znaleziono: 1 artykuł.');
    }

    public function test_akcje_zbiorcze(): void
    {
        $admin = $this->admin();
        $cat = BlogCategory::create(['name' => 'Prawo', 'slug' => 'prawo']);
        $a = $this->article('A', ['is_published' => false, 'published_at' => null]);
        $b = $this->article('B');
        $ids = [$a->id, $b->id];
        $bulk = fn (array $data) => $this->actingAs($admin)->post(route('admin.wiem-feer.zbiorczo'), $data);

        $bulk(['ids' => $ids, 'action' => 'publish'])->assertRedirect(route('admin.wiem-feer.index'));
        $this->assertTrue($a->fresh()->is_published);
        $this->assertNotNull($a->fresh()->published_at);

        $bulk(['ids' => $ids, 'action' => 'draft'])->assertRedirect();
        $this->assertSame(0, BlogArticle::where('is_published', true)->count());

        $bulk(['ids' => [$a->id], 'action' => 'disable'])->assertRedirect();
        $this->assertTrue($a->fresh()->is_disabled);
        $bulk(['ids' => [$a->id], 'action' => 'enable'])->assertRedirect();
        $this->assertFalse($a->fresh()->is_disabled);

        $bulk(['ids' => $ids, 'action' => 'category', 'category_id' => (string) $cat->id])->assertRedirect();
        $this->assertSame(2, BlogArticle::where('category_id', $cat->id)->count());
        $bulk(['ids' => [$b->id], 'action' => 'category', 'category_id' => 'brak'])->assertRedirect();
        $this->assertNull($b->fresh()->category_id);
        $bulk(['ids' => [$b->id], 'action' => 'category', 'category_id' => '999'])->assertSessionHasErrors('category_id');

        // Walidacja: brak zaznaczenia, zła akcja, usunięcie bez potwierdzenia.
        $bulk(['action' => 'publish'])->assertSessionHasErrors('ids');
        $bulk(['ids' => $ids, 'action' => 'hack'])->assertSessionHasErrors('action');
        $bulk(['ids' => [99999], 'action' => 'publish'])->assertSessionHasErrors('ids.0');
        $bulk(['ids' => $ids, 'action' => 'delete'])->assertSessionHasErrors('confirm_delete');
        $this->assertSame(2, BlogArticle::count());

        // Powrót na listę z filtrami.
        $back = route('admin.wiem-feer.index', ['status' => 'draft']);
        $bulk(['ids' => $ids, 'action' => 'delete', 'confirm_delete' => '1', 'return_to' => $back])->assertRedirect($back);
        $this->assertSame(0, BlogArticle::count());
    }

    public function test_akcje_zbiorcze_wymagaja_dostepu_do_modulu(): void
    {
        $a = $this->article('A', ['is_published' => false]);

        $this->post(route('admin.wiem-feer.zbiorczo'), ['ids' => [$a->id], 'action' => 'publish'])->assertRedirect(route('login'));

        $bip = User::factory()->create(['role' => User::ROLE_BIP_EDITOR]);
        $this->actingAs($bip)->post(route('admin.wiem-feer.zbiorczo'), ['ids' => [$a->id], 'action' => 'publish'])->assertForbidden();
        $this->actingAs($bip)->get(route('admin.wiem-feer.index'))->assertForbidden();
        $this->assertFalse($a->fresh()->is_published);

        $pr = User::factory()->create(['role' => User::ROLE_PR_EDITOR]);
        $this->actingAs($pr)->post(route('admin.wiem-feer.zbiorczo'), ['ids' => [$a->id], 'action' => 'publish'])->assertRedirect();
        $this->assertTrue($a->fresh()->is_published);
    }

    public function test_powrot_tylko_na_liste(): void
    {
        $a = $this->article('A');
        $this->actingAs($this->admin())->post(route('admin.wiem-feer.zbiorczo'), ['ids' => [$a->id], 'action' => 'disable', 'return_to' => 'https://evil.example/'])
            ->assertRedirect(route('admin.wiem-feer.index'));
    }
}
