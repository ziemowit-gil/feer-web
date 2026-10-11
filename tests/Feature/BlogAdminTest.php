<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogCategory;

class BlogAdminTest extends BlogTestCase
{
    /** @param array<string, mixed> $extra */
    private function form(array $extra = []): array
    {
        return array_merge(['title' => 'Nowy wpis', 'body' => '<p>Treść</p>', 'is_published' => '1'], $extra);
    }

    public function test_kategorie_crud(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.kategorie-bloga.index'))->assertOk()->assertSee('Nowa kategoria');
        $this->actingAs($admin)->post(route('admin.kategorie-bloga.store'), ['name' => 'Dostępność cyfrowa'])->assertRedirect(route('admin.kategorie-bloga.index'));
        $cat = BlogCategory::firstOrFail();
        $this->assertSame('dostepnosc-cyfrowa', $cat->slug);

        $this->actingAs($admin)->post(route('admin.kategorie-bloga.store'), ['name' => 'Dostępność cyfrowa'])->assertSessionHasErrors('_slug');

        $this->actingAs($admin)->get(route('admin.kategorie-bloga.edit', $cat))->assertOk();
        $this->actingAs($admin)->put(route('admin.kategorie-bloga.update', $cat), ['name' => 'Dostępność', 'slug' => 'dostepnosc', 'sort_order' => 3])->assertRedirect();
        $this->assertSame('dostepnosc', $cat->fresh()->slug);

        $article = $this->article('W kategorii', ['category_id' => $cat->id]);
        $this->actingAs($admin)->delete(route('admin.kategorie-bloga.destroy', $cat))->assertRedirect();
        $this->assertSame(0, BlogCategory::count());
        $this->assertNull($article->fresh()->category_id);
    }

    public function test_zapis_wpisu_z_kategoria_tagami_i_seo(): void
    {
        $cat = BlogCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja']);

        $this->actingAs($this->admin())->post(route('admin.wiem-feer.store'), $this->form([
            'category_id' => $cat->id, 'tags' => 'WCAG, Dostępność,  wcag , ', 'is_featured' => '1',
            'meta_title' => 'Tytuł SEO', 'meta_description' => 'Opis SEO',
        ]))->assertRedirect(route('admin.wiem-feer.index'));

        $article = BlogArticle::firstOrFail();
        $this->assertSame($cat->id, $article->category_id);
        $this->assertSame(['Dostępność', 'WCAG'], $article->tags->pluck('name')->all());
        $this->assertTrue($article->is_featured);
        $this->assertSame('Tytuł SEO', $article->meta_title);

        $this->actingAs($this->admin())->get(route('admin.wiem-feer.edit', $article))->assertOk()
            ->assertSee('value="Dostępność, WCAG"', false);
    }

    public function test_zdjecie_wymaga_altu_albo_oznaczenia_dekoracyjnego(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.wiem-feer.store'), $this->form(['image' => UploadedFile::fake()->image('a.jpg')]))
            ->assertSessionHasErrors('image_alt');
        $this->assertSame(0, BlogArticle::count());

        $this->actingAs($admin)->post(route('admin.wiem-feer.store'), $this->form(['image' => UploadedFile::fake()->image('a.jpg'), 'image_alt' => 'Opis zdjęcia']))
            ->assertRedirect();
        $article = BlogArticle::firstOrFail();
        Storage::disk('public')->assertExists($article->image_path);
        $this->assertSame('Opis zdjęcia', $article->image_alt);

        // Dekoracyjne — bez altu przechodzi; usunięcie zdjęcia czyści plik.
        $this->actingAs($admin)->put(route('admin.wiem-feer.update', $article), $this->form(['image_alt' => '', 'image_decorative' => '1']))->assertRedirect();
        $this->assertTrue($article->fresh()->image_decorative);

        $path = $article->image_path;
        $this->actingAs($admin)->put(route('admin.wiem-feer.update', $article), $this->form(['remove_image' => '1']))->assertRedirect();
        $this->assertNull($article->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_lista_pokazuje_status_tekstem_i_filtruje(): void
    {
        $this->article('Opublikowany wpis');
        $this->article('Zaplanowany wpis', ['published_at' => now()->addDays(3)]);
        $this->article('Szkic wpisu', ['is_published' => false]);
        $this->article('Wyłączony wpis', ['is_disabled' => true]);

        $response = $this->actingAs($this->admin())->get(route('admin.wiem-feer.index'))->assertOk();
        $response->assertSee('Zaplanowany')->assertSee('Szkic')->assertSee('Wyłączony')->assertDontSee('rounded-full bg-green-100', false);

        $this->actingAs($this->admin())->get(route('admin.wiem-feer.index', ['status' => 'scheduled']))->assertOk()
            ->assertSee('Zaplanowany wpis')->assertDontSee('Opublikowany wpis')->assertDontSee('Szkic wpisu');
        $this->actingAs($this->admin())->get(route('admin.wiem-feer.index', ['status' => 'disabled']))->assertOk()
            ->assertSee('Wyłączony wpis')->assertDontSee('Zaplanowany wpis');
    }
}
