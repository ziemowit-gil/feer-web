<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogComment;
use Tests\Feature\BlogTestCase;

/** Osobny pulpit „Wiem FEER”, skupione menu witryny bloga i kontekstowy pasek „Szybko:”. */
class BlogDashboardTest extends BlogTestCase
{
    private function blogSite(): SiteSetting
    {
        $main = SiteSetting::current();

        return SiteSetting::create(['site_name' => 'Wiem FEER', 'slug' => config('blog.site_slug', 'wiem'), 'parent_site_id' => $main->id]);
    }

    private function seedBlog(): void
    {
        $this->article('Opublikowany kompletny', ['excerpt' => 'Zajawka', 'meta_description' => 'Opis', 'category_id' => null]);
        $this->article('Opublikowany drugi');
        $this->article('Zaplanowany wpis', ['published_at' => now()->addDays(3)]);
        $draft = $this->article('Stary szkic', ['is_published' => false, 'published_at' => null]);
        DB::connection('blog')->table('blog_articles')->where('id', $draft->id)->update(['updated_at' => now()->subDays(20)]);
        $this->article('Świeży szkic', ['is_published' => false, 'published_at' => null]);
        $this->article('Wyłączony wpis', ['is_disabled' => true]);

        $article = $this->article('Z komentarzami');
        BlogComment::create(['article_id' => $article->id, 'author_name' => 'Anna', 'email' => 'a@example.org', 'body' => 'Czekam na moderację', 'is_approved' => false]);
        BlogComment::create(['article_id' => $article->id, 'author_name' => 'Jan', 'email' => 'j@example.org', 'body' => 'Też czekam', 'is_approved' => false]);
        BlogComment::create(['article_id' => $article->id, 'author_name' => 'Ola', 'email' => 'o@example.org', 'body' => 'Zatwierdzony', 'is_approved' => true]);

        BlogAuthor::create(['name' => 'Pełny Autor', 'slug' => 'pelny-autor', 'bio' => 'Bio', 'photo_path' => 'blog/a.jpg', 'is_active' => true]);
        BlogAuthor::create(['name' => 'Bez Bio', 'slug' => 'bez-bio', 'is_active' => true]);
        BlogAuthor::create(['name' => 'Nieaktywny', 'slug' => 'nieaktywny', 'is_active' => false]);
    }

    public function test_pulpit_pokazuje_liczniki_i_liste_do_zrobienia(): void
    {
        $this->seedBlog();

        $response = $this->actingAs($this->admin())->get(route('admin.wiem.dashboard'))->assertOk();
        $html = $response->getContent();

        $this->assertStat($html, 'Opublikowane', 3);
        $this->assertStat($html, 'Zaplanowane', 1);
        $this->assertStat($html, 'Szkice', 2);
        $this->assertStat($html, 'Wyłączone lub w przygotowaniu', 1);
        $this->assertStat($html, 'Komentarze do moderacji', 2);
        $this->assertStat($html, 'Aktywni autorzy', BlogAuthor::active()->count());

        // Do zrobienia: stary szkic tak, świeży nie; niepełny profil autora; braki SEO.
        $this->assertStringContainsString('Stary szkic</a>', $html);
        $this->assertStringNotContainsString('Świeży szkic</a> <span class="text-muted">— ostatnia zmiana', $html);
        $this->assertMatchesRegularExpression('/Bez Bio<\/a>\s*<span class="text-muted">— brak: bio, zdjęcie/', $html);
        $this->assertStringNotContainsString('Pełny Autor</a>', $html);
        $this->assertMatchesRegularExpression('/Opublikowany kompletny<\/a>\s*<span class="text-muted">— brak kategorii<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Opublikowany drugi<\/a>\s*<span class="text-muted">— [^<]*brak opisu meta/', $html);

        $response->assertSee('Do zrobienia')
            ->assertSee('Czekam na moderację')
            ->assertSee('Nowy wpis')
            ->assertSee('Zaplanowany wpis')
            ->assertDontSee('Najpopularniejsze');
    }

    public function test_brak_bazy_bloga_pokazuje_komunikat(): void
    {
        Schema::connection('blog')->drop('blog_articles');

        $this->actingAs($this->admin())->get(route('admin.wiem.dashboard'))
            ->assertOk()
            ->assertSee('Baza bloga jest niedostępna.');
    }

    public function test_bez_dostepu_do_bloga_403(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COORDINATOR]);

        $this->actingAs($user)->get(route('admin.wiem.dashboard'))->assertForbidden();
    }

    public function test_dashboard_przekierowuje_na_pulpit_bloga_gdy_aktywna_witryna_bloga(): void
    {
        $admin = $this->admin();
        $site = $this->blogSite();

        $this->actingAs($admin)->withSession(['admin_active_site_id' => $site->id])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.wiem.dashboard'));
    }

    public function test_dashboard_bez_witryny_bloga_pokazuje_zwykly_pulpit(): void
    {
        $this->blogSite();

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Pulpit bloga — wpisy, moderacja');
    }

    public function test_menu_witryny_bloga_pokazuje_blog_i_ukrywa_moduly_cms(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $normal = $this->itemKeys(AdminMenu::for($admin));
        $this->assertContains('news', $normal);
        $this->assertNotContains('blog-dashboard', $normal);

        session(['admin_active_site_id' => $this->blogSite()->id]);
        $groups = AdminMenu::for($admin);
        $keys = $this->itemKeys($groups);

        $this->assertSame('start', $groups[0]['key']);
        $this->assertSame(['blog-dashboard', 'blog-articles', 'blog-categories', 'blog-authors', 'blog-comments'],
            array_slice(array_column($groups[0]['blocks'][0]['items'], 'key'), 0, 5));
        foreach (['pages', 'media', 'settings'] as $kept) {
            $this->assertContains($kept, $keys);
        }
        foreach (['news', 'projects', 'bip', 'hero', 'dashboard'] as $hidden) {
            $this->assertNotContains($hidden, $keys);
        }
    }

    public function test_pasek_szybko_na_ekranach_bloga_jest_kontekstowy(): void
    {
        $this->seedBlog();

        $html = $this->actingAs($this->admin())->get(route('admin.wiem-feer.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-quickbar="blog"', $html);
        $this->assertStringContainsString('Komentarze do moderacji (2)', $html);
        $this->assertStringContainsString('Pulpit Wiem FEER', $html);
        $this->assertStringContainsString('Zobacz blog', $html);
        $this->assertStringNotContainsString('Dodaj aktualność', $html);
        $this->assertStringContainsString('data-blog-subnav', $html);
        $this->assertMatchesRegularExpression('/aria-current="page"\s+class="[^"]*font-extrabold[^"]*">Wpisy</', $html);
    }

    public function test_pasek_szybko_poza_blogiem_bez_zmian(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-quickbar="blog"', $html);
        $this->assertStringNotContainsString('data-blog-subnav', $html);
        $this->assertStringContainsString('Dodaj aktualność', $html);
    }

    public function test_pasek_szybko_blogowy_gdy_aktywna_witryna_bloga(): void
    {
        $site = $this->blogSite();

        $html = $this->actingAs($this->admin())->withSession(['admin_active_site_id' => $site->id])
            ->get(route('admin.podstrony.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-quickbar="blog"', $html);
        $this->assertStringNotContainsString('Dodaj aktualność', $html);
    }

    private function assertStat(string $html, string $label, int $count): void
    {
        $this->assertMatchesRegularExpression(
            '/>\s*'.$count.'\s*<\/span>\s*<span[^>]*>\s*'.preg_quote($label, '/').'\s*</u', $html, "Licznik „{$label}” ≠ {$count}");
    }

    /** @param array<int, array<string, mixed>> $groups @return array<int, string> */
    private function itemKeys(array $groups): array
    {
        return collect($groups)->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();
    }
}
