<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;

class BlogAuthorsTest extends BlogTestCase
{
    public function test_crud_autora_i_dezaktywacja(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.autorzy-bloga.index'))->assertOk()->assertSee('Dodaj autora');
        $this->actingAs($admin)->get(route('admin.autorzy-bloga.create'))->assertOk()->assertSee('Konto w CMS');

        $this->actingAs($admin)->post(route('admin.autorzy-bloga.store'), [
            'name' => 'Anna Nowak', 'role' => 'Trenerka dostępności', 'bio' => 'Nota', 'is_active' => '1',
            'links' => "LinkedIn | https://linkedin.example/anna\nhttps://anna.example", 'user_id' => $admin->id,
        ])->assertRedirect(route('admin.autorzy-bloga.index'));

        $author = BlogAuthor::firstOrFail();
        $this->assertSame('anna-nowak', $author->slug);
        $this->assertSame([['label' => 'LinkedIn', 'url' => 'https://linkedin.example/anna'], ['label' => '', 'url' => 'https://anna.example']], $author->links);
        $this->assertSame('anna.example', $author->linkList()[1]['label']);

        $this->actingAs($admin)->post(route('admin.autorzy-bloga.store'), ['name' => 'Ktoś', 'links' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('links');
        $this->actingAs($admin)->post(route('admin.autorzy-bloga.store'), ['name' => 'Anna Nowak'])->assertSessionHasErrors('slug');

        $this->actingAs($admin)->get(route('admin.autorzy-bloga.edit', $author))->assertOk()
            ->assertSee('LinkedIn | https://linkedin.example/anna', false);
        $this->actingAs($admin)->put(route('admin.autorzy-bloga.update', $author), ['name' => 'Anna Nowak-Kowalska', 'slug' => 'anna-nowak', 'is_active' => '1'])->assertRedirect();
        $this->assertSame('Anna Nowak-Kowalska', $author->fresh()->name);

        $this->actingAs($admin)->patch(route('admin.autorzy-bloga.aktywnosc', $author))->assertRedirect();
        $this->assertFalse($author->fresh()->is_active);
    }

    public function test_nowy_wpis_ma_domyslnie_autora_zalogowanego_i_zapisuje_wspolautorow(): void
    {
        $admin = $this->admin();
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak', 'user_id' => $admin->id]);
        $jan = BlogAuthor::create(['name' => 'Jan Kowalski', 'slug' => 'jan-kowalski']);
        $ewa = BlogAuthor::create(['name' => 'Ewa Lis', 'slug' => 'ewa-lis']);
        BlogAuthor::create(['name' => 'Nieaktywny Autor', 'slug' => 'nieaktywny', 'is_active' => false]);

        $this->actingAs($admin)->get(route('admin.wiem-feer.create'))->assertOk()
            ->assertSee('<option value="' . $anna->id . '" selected>', false)
            ->assertDontSee('Nieaktywny Autor');

        $this->actingAs($admin)->post(route('admin.wiem-feer.store'), [
            'title' => 'Wspólny tekst', 'body' => '<p>x</p>', 'is_published' => '1',
            'author_id' => $anna->id, 'co_authors' => [$ewa->id, $anna->id, $jan->id],
        ])->assertRedirect();

        $article = BlogArticle::firstOrFail();
        $this->assertSame($anna->id, $article->author_id);
        $this->assertSame(['Ewa Lis', 'Jan Kowalski'], $article->coAuthors->pluck('name')->all());
        $this->assertSame('Anna Nowak, Ewa Lis i Jan Kowalski', $article->authorLine());

        $this->get('/wiem/wspolny-tekst')->assertOk()->assertSee('O autorach')->assertSee('Autorzy:');
    }

    public function test_strona_autora_pokazuje_tylko_jego_opublikowane_wpisy(): void
    {
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak', 'bio' => 'Bio Anny']);
        $jan = BlogAuthor::create(['name' => 'Jan Kowalski', 'slug' => 'jan-kowalski']);
        $this->article('Tekst Anny', ['author_id' => $anna->id]);
        $co = $this->article('Wspólny', ['author_id' => $jan->id]);
        $co->coAuthors()->sync([$anna->id => ['position' => 0]]);
        $this->article('Szkic Anny', ['author_id' => $anna->id, 'is_published' => false]);
        $this->article('Tekst Jana', ['author_id' => $jan->id]);

        $this->get('/wiem/autor/anna-nowak')->assertOk()
            ->assertSee('<h1 class="mt-2 text-3xl font-bold text-ink md:text-4xl">Anna Nowak</h1>', false)
            ->assertSee('Bio Anny')
            ->assertSee('Tekst Anny')->assertSee('Wspólny')
            ->assertDontSee('Szkic Anny')->assertDontSee('Tekst Jana')
            ->assertSee('"@type":"ProfilePage"', false);

        $this->get('/wiem/autorzy')->assertOk()->assertSee('Anna Nowak')->assertSee('Jan Kowalski');
        $this->get('/wiem/autor/nie-ma')->assertNotFound();
    }

    public function test_migracja_danych_przenosi_author_name_do_profili_i_jest_idempotentna(): void
    {
        $this->article('Stary 1', ['author_name' => 'Zespół FEER']);
        $this->article('Stary 2', ['author_name' => 'Zespół FEER']);
        $this->article('Stary 3', ['author_name' => 'Maria Wiśniewska']);
        $this->article('Bez autora');
        $existing = BlogAuthor::create(['name' => 'Maria Wiśniewska', 'slug' => 'maria-wisniewska', 'role' => 'Redaktorka']);

        $migration = fn () => (require base_path('database/migrations/blog/2026_10_11_000003_import_blog_authors_from_author_name.php'))->up();
        $migration();
        $migration();

        $this->assertSame(2, BlogAuthor::count());
        $team = BlogAuthor::where('slug', 'zespol-feer')->firstOrFail();
        $this->assertSame(2, BlogArticle::where('author_id', $team->id)->count());
        $this->assertSame($existing->id, BlogArticle::where('title', 'Stary 3')->value('author_id'));
        $this->assertNull(BlogArticle::where('title', 'Bez autora')->value('author_id'));
        $this->assertSame('Zespół FEER', DB::connection('blog')->table('blog_articles')->where('title', 'Stary 1')->value('author_name'));
    }
}
