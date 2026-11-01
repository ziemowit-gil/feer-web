<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** „Kopiuj do witryny” dla partnerów, aktualności i wydarzeń (strony: PageSiteSharingTest). */
class ContentCopyToSiteTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_kopiuje_partnera_do_witryny(): void
    {
        $partner = Partner::create(['site_id' => $this->main->id, 'name' => 'Firma X', 'url' => 'https://x.example', 'order' => 3]);

        $this->actingAs($this->admin())->post(route('admin.partnerzy.copy-to-site', $partner), ['target_site_id' => $this->sub->id])
            ->assertRedirect()->assertSessionHas('status');

        $copy = Partner::withoutGlobalScopes()->where('site_id', $this->sub->id)->sole();
        $this->assertSame(['Firma X', 'https://x.example', 3], [$copy->name, $copy->url, $copy->order]);
        $this->assertSame($this->main->id, $partner->fresh()->site_id);
    }

    public function test_kopiuje_aktualnosc_jako_szkic_z_nowym_slugiem(): void
    {
        $news = News::withoutGlobalScopes()->create([
            'site_id' => $this->main->id, 'title' => 'Wieść', 'slug' => 'wiesc', 'content' => '<p>Treść</p>',
            'is_published' => true, 'published_at' => now(),
        ]);

        $this->actingAs($this->admin())->post(route('admin.newsy.copy-to-site', $news), ['target_site_id' => $this->sub->id])
            ->assertRedirect()->assertSessionHas('status');

        $copy = News::withoutGlobalScopes()->where('site_id', $this->sub->id)->sole();
        $this->assertSame('Wieść', $copy->title);
        $this->assertNotSame('wiesc', $copy->slug);
        $this->assertFalse((bool) $copy->is_published);
        $this->assertNull($copy->published_at);
    }

    public function test_kopiuje_wydarzenie_jako_szkic_z_tym_samym_terminem(): void
    {
        $event = Event::withoutGlobalScopes()->create([
            'site_id' => $this->main->id, 'title' => 'Warsztat', 'slug' => 'warsztat', 'lead' => 'Krótki opis',
            'is_published' => true, 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2),
        ]);
        $event->faqs()->create(['question' => 'Czy płatne?', 'answer' => 'Nie.', 'order' => 1]);

        $this->actingAs($this->admin())->post(route('admin.wydarzenia.copy-to-site', $event), ['target_site_id' => $this->sub->id])
            ->assertRedirect()->assertSessionHas('status');

        $copy = Event::withoutGlobalScopes()->where('site_id', $this->sub->id)->sole();
        $this->assertFalse((bool) $copy->is_published);
        $this->assertTrue($copy->starts_at->equalTo($event->starts_at));
        $this->assertNotSame('warsztat', $copy->slug);
        $this->assertSame(['Czy płatne?'], $copy->faqs()->pluck('question')->all());
    }

    private function newsWithPhotoAndTag(): News
    {
        $news = News::withoutGlobalScopes()->create([
            'site_id' => $this->main->id, 'title' => 'Ze zdjęciem', 'slug' => 'ze-zdjeciem', 'content' => '<p>Treść</p>',
            'is_published' => true, 'published_at' => now(),
        ]);
        $news->addMedia(UploadedFile::fake()->image('foto.jpg', 800, 600))->toMediaCollection('image');
        $news->tags()->sync([Tag::create(['name' => 'Edukacja', 'slug' => 'edukacja'])->id, Tag::create(['name' => 'Seniorzy', 'slug' => 'seniorzy'])->id]);

        return $news;
    }

    public function test_kopia_aktualnosci_do_witryny_zabiera_zdjecie_i_tagi(): void
    {
        $news = $this->newsWithPhotoAndTag();

        $this->actingAs($this->admin())->post(route('admin.newsy.copy-to-site', $news), ['target_site_id' => $this->sub->id])
            ->assertRedirect()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'ze zdjęciem i tagami'));

        $copy = News::withoutGlobalScopes()->where('site_id', $this->sub->id)->sole();
        $this->assertNotNull($copy->getFirstMedia('image'));
        $this->assertEqualsCanonicalizing(['edukacja', 'seniorzy'], $copy->tags->pluck('slug')->all());
        // Oryginał zostaje nietknięty.
        $this->assertNotNull($news->fresh()->getFirstMedia('image'));
        $this->assertCount(2, $news->fresh()->tags);
    }

    public function test_okno_kopiowania_pozwala_pominac_zdjecie_i_tagi(): void
    {
        $news = $this->newsWithPhotoAndTag();
        $admin = $this->admin();

        // Odznaczone pola wysyłają 0 (ukryte pole przed polem wyboru).
        $this->actingAs($admin)->post(route('admin.newsy.copy-to-site', $news), ['target_site_id' => $this->sub->id, 'copy_image' => '0', 'copy_tags' => '0'])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'bez zdjęcia i tagów'));
        $bare = News::withoutGlobalScopes()->where('site_id', $this->sub->id)->sole();
        $this->assertNull($bare->getFirstMedia('image'));
        $this->assertCount(0, $bare->tags);

        $this->actingAs($admin)->post(route('admin.newsy.copy-to-site', $news), ['target_site_id' => $this->sub->id, 'copy_image' => '1', 'copy_tags' => '0'])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'ze zdjęciem, bez tagów'));
        $withPhoto = News::withoutGlobalScopes()->where('site_id', $this->sub->id)->latest('id')->first();
        $this->assertNotNull($withPhoto->getFirstMedia('image'));
        $this->assertCount(0, $withPhoto->tags);

        $this->actingAs($admin)->post(route('admin.newsy.copy-to-site', $news), ['target_site_id' => $this->sub->id, 'copy_image' => '0', 'copy_tags' => '1'])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'z tagami, bez zdjęcia'));
        $withTags = News::withoutGlobalScopes()->where('site_id', $this->sub->id)->latest('id')->first();
        $this->assertNull($withTags->getFirstMedia('image'));
        $this->assertCount(2, $withTags->tags);
    }

    public function test_klonowanie_aktualnosci_kopiuje_zdjecie_i_tagi(): void
    {
        $news = $this->newsWithPhotoAndTag();

        $this->actingAs($this->admin())->post(route('admin.newsy.klonuj', $news))
            ->assertRedirect()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'ze zdjęciem i tagami'));

        $clone = News::withoutGlobalScopes()->where('cloned_from_id', $news->id)->sole();
        $this->assertFalse((bool) $clone->is_published);
        $this->assertNotNull($clone->getFirstMedia('image'));
        $this->assertCount(2, $clone->tags);
    }

    public function test_lista_aktualnosci_ma_jedno_okno_kopiowania_poza_formularzem_zbiorczym(): void
    {
        foreach (['Pierwsza', 'Druga', 'Trzecia'] as $i => $title) {
            News::withoutGlobalScopes()->create([
                'site_id' => $this->main->id, 'title' => $title, 'slug' => 'n-'.$i, 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now(),
            ]);
        }

        $html = $this->actingAs($this->admin())->get(route('admin.newsy.index'))->assertOk()->getContent();

        // Jedno <dialog> na stronę zamiast formularza przy każdym wierszu; dopisane na koniec strony, więc ani przycięte
        // przez tabelę (overflow-hidden), ani zagnieżdżone w formularzu zbiorczym listy.
        $this->assertSame(1, substr_count($html, '<dialog id="copy-to-site-dialog"'));
        $this->assertSame(3, substr_count($html, 'data-copy-action="'));
        $this->assertGreaterThan(strrpos($html, 'id="bulk-form"'), strpos($html, '<dialog id="copy-to-site-dialog"'));
        $this->assertDoesNotMatchRegularExpression('~<form[^>]*kopiuj-do-witryny~', $html);
        // Przycisk ma nazwę dostępną (czytnik ekranu), wskazuje okno i podaje, co można skopiować.
        $this->assertStringContainsString('aria-haspopup="dialog"', $html);
        $this->assertStringContainsString('Kopiuj do witryny: Pierwsza', $html);
        $this->assertStringContainsString('aria-labelledby="copy-to-site-title"', $html);
        $this->assertStringContainsString('copy_image', $html);
        $this->assertStringContainsString('copy_tags', $html);
        $this->assertStringContainsString('Co skopiować razem z treścią', $html);
    }

    public function test_okna_kopiowania_nie_ma_dla_redaktora_ani_bez_drugiej_witryny(): void
    {
        News::withoutGlobalScopes()->create(['site_id' => $this->main->id, 'title' => 'Wieść', 'slug' => 'w', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()]);

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->get(route('admin.newsy.index'))->assertOk()->assertDontSee('copy-to-site-dialog', false);

        $this->sub->delete();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->actingAs($this->admin())->get(route('admin.newsy.index'))->assertOk()->assertDontSee('copy-to-site-dialog', false);
    }

    public function test_drzewo_stron_uzywa_tego_samego_okna_kopiowania(): void
    {
        $page = Page::create(['site_id' => $this->main->id, 'title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true]);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<dialog id="copy-to-site-dialog"'));
        $this->assertStringContainsString('data-copy-action="'.route('admin.podstrony.copy-to-site', $page).'"', $html);
        $this->assertStringContainsString('Kopiuj do witryny', $html);
        $this->assertStringNotContainsString('id="copy-to-site-panel"', $html);
    }

    public function test_wymaga_admina_i_innej_witryny(): void
    {
        $partner = Partner::create(['site_id' => $this->main->id, 'name' => 'Firma X', 'order' => 1]);
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)->post(route('admin.partnerzy.copy-to-site', $partner), ['target_site_id' => $this->sub->id])->assertForbidden();

        $this->actingAs($this->admin())->post(route('admin.partnerzy.copy-to-site', $partner), ['target_site_id' => $this->main->id])
            ->assertSessionHas('error');
        $this->assertSame(0, Partner::withoutGlobalScopes()->where('site_id', $this->sub->id)->count());
    }

    public function test_klonowanie_wydarzenia_tworzy_szkic_z_terminem_oryginalu(): void
    {
        $event = Event::withoutGlobalScopes()->create([
            'site_id' => $this->main->id, 'title' => 'Warsztat', 'slug' => 'warsztat', 'lead' => 'Opis',
            'is_published' => true, 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2),
        ]);

        $this->actingAs($this->admin())->post(route('admin.wydarzenia.klonuj', $event))->assertRedirect();

        $clone = Event::withoutGlobalScopes()->where('title', 'Warsztat (kopia)')->sole();
        $this->assertFalse((bool) $clone->is_published);
        $this->assertTrue($clone->starts_at->equalTo($event->starts_at));
    }
}
