<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\News;
use App\Models\Partner;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
