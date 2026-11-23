<?php

namespace Tests\Feature;

use App\Models\KreatywniDocument;
use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mikro moduł „Dokumenty – Kreatywni”: panel (plik, strona, adres, ukrywanie, kolejność, usuwanie, walidacja),
 * przeniesienie dokumentów ze stron pod „Fundacją” (panel i migracja), sekcja #dokumenty na stronie „O nas”
 * i w mega menu, działanie bez tabeli i tylko w szablonie „kreatywni”.
 */
class KreatywniDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function useTemplate(string $template = 'kreatywni', array $extra = []): SiteSetting
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => $template] + $extra)->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return SiteSetting::current();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function aboutPage(): Page
    {
        return Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'content' => '']);
    }

    /** Strona „Fundacja” z podstronami dokumentów, jak zakłada KreatywniSiteSeeder. */
    private function foundationDocs(): array
    {
        $foundation = Page::create(['title' => 'Fundacja', 'slug' => 'fundacja', 'type' => 'standard', 'is_published' => true, 'content' => '<p>[podstrony]</p>']);
        $statute = Page::create(['title' => 'Statut Fundacji', 'slug' => 'statut-fundacji', 'type' => 'standard', 'parent_id' => $foundation->id, 'order' => 1, 'is_published' => false, 'content' => '<p>Do uzupełnienia.</p>']);
        $privacy = Page::create(['title' => 'Polityka prywatności', 'slug' => 'polityka-prywatnosci', 'type' => 'standard', 'parent_id' => $foundation->id, 'order' => 2, 'is_published' => true, 'content' => '<p>Pełny tekst.</p>']);
        $cookies = Page::create(['title' => 'Polityka cookies', 'slug' => 'polityka-cookies', 'type' => 'standard', 'parent_id' => $foundation->id, 'order' => 3, 'is_published' => false, 'content' => '<p>Do uzupełnienia.</p>']);
        $attachment = $statute->attachments()->create(['label' => 'Statut (PDF)', 'order' => 1]);
        $attachment->addMedia(UploadedFile::fake()->create('statut.pdf', 120, 'application/pdf'))->toMediaCollection('file');

        return compact('foundation', 'statute', 'privacy', 'cookies');
    }

    public function test_panel_dodaje_dokument_z_plikiem_a_strona_o_nas_go_pokazuje(): void
    {
        $this->useTemplate();
        $this->aboutPage();
        $this->actingAs($this->admin());

        $this->get(route('admin.kreatywni-dokumenty.index'))->assertOk()
            ->assertSee('Dokumenty – Kreatywni')->assertSee('Nie ma jeszcze dokumentów w module')->assertDontSee('Przenieś dokumenty ze stron');
        $this->get(route('admin.kreatywni-dokumenty.create'))->assertOk()
            ->assertSee('<label for="kd-title"', false)->assertSee('<label for="kd-file"', false)->assertSee('<label for="kd-page"', false);

        $this->post(route('admin.kreatywni-dokumenty.store'), [
            'title' => 'Statut Fundacji', 'description' => 'Cele i władze Fundacji.', 'is_active' => '1',
            'file' => UploadedFile::fake()->create('statut.pdf', 240, 'application/pdf'),
        ])->assertRedirect(route('admin.kreatywni-dokumenty.index'))->assertSessionHasNoErrors();

        $document = KreatywniDocument::query()->sole();
        $this->assertStringStartsWith('PDF, ', $document->fileMeta());
        $this->get(route('admin.kreatywni-dokumenty.index'))->assertSee('Plik (PDF, ', false)->assertSee('Widoczny');

        $html = $this->get('/o-nas')->assertOk()->getContent();
        $this->assertStringContainsString('<section id="dokumenty" class="kc-sec" aria-labelledby="kc-about-docs">', $html);
        $this->assertMatchesRegularExpression('#<a href="[^"]+statut\.pdf">Statut Fundacji <span class="kc-doc-meta">\(PDF, [^)]+\)</span>#', $html);
        $this->assertStringContainsString('<p>Cele i władze Fundacji.</p>', $html);
        $this->assertStringContainsString('fa-file-pdf', $html);
        $this->assertLessThan(strpos($html, 'id="dolacz"'), strpos($html, 'id="dokumenty"'), 'Dokumenty przed zaproszeniem.');
    }

    public function test_przeniesienie_ze_stron_pod_fundacja_z_plikami_i_stronami(): void
    {
        $this->useTemplate();
        $this->aboutPage();
        $docs = $this->foundationDocs();
        $this->actingAs($this->admin());

        $this->get(route('admin.kreatywni-dokumenty.index'))->assertSee('Przenieś dokumenty ze stron (3)');
        $this->post(route('admin.kreatywni-dokumenty.import'))->assertRedirect()->assertSessionHas('status');
        $this->assertSame(['Statut Fundacji', 'Polityka prywatności', 'Polityka cookies'], KreatywniDocument::query()->orderBy('order')->pluck('title')->all());
        $this->post(route('admin.kreatywni-dokumenty.import'));
        $this->assertSame(3, KreatywniDocument::query()->count(), 'Drugi import nic nie dubluje.');

        // Statut: plik z załącznika szkicu strony; polityka prywatności: opublikowana strona; cookies: szkic bez pliku —
        // na liście jako „w przygotowaniu”, bez linku.
        $html = $this->get('/o-nas')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#statut\.pdf"\s*>Statut Fundacji <span class="kc-doc-meta">\(PDF#', $html);
        $this->assertStringContainsString('href="'.$docs['privacy']->publicUrl().'"', $html);
        $this->assertStringContainsString('<span class="kc-doc-name">Polityka cookies</span> <span class="kc-doc-pending">— w przygotowaniu</span>', $html);
        $this->assertStringNotContainsString('href="'.$docs['cookies']->publicUrl().'"', $html);
        $this->get(route('admin.kreatywni-dokumenty.index'))->assertSee('Strona „Polityka cookies” jest szkicem')->assertSee('„W przygotowaniu”, bez linku', false);

        // Po opublikowaniu strony dokument dostaje link sam.
        $docs['cookies']->update(['is_published' => true]);
        $this->get('/o-nas')->assertSee('href="'.$docs['cookies']->publicUrl().'"', false)->assertDontSee('— w przygotowaniu');
    }

    public function test_kolejnosc_ukrywanie_usuwanie_i_mega_menu(): void
    {
        $this->useTemplate();
        $this->aboutPage();
        NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        $this->actingAs($this->admin());

        $this->assertStringNotContainsString('href="/o-nas#dokumenty"', $this->get('/')->getContent(), 'Bez dokumentów nie ma kotwicy.');
        $this->post(route('admin.kreatywni-dokumenty.store'), ['title' => 'Regulamin darowizn', 'url' => 'https://example.org/regulamin.pdf', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->post(route('admin.kreatywni-dokumenty.store'), ['title' => 'Sprawozdanie', 'url' => '/sprawozdanie', 'is_active' => '1'])->assertSessionHasNoErrors();
        $home = $this->get('/')->getContent();
        // Blok „Dokumenty” w rozwijanym menu „O nas”: dokumenty, „Wszystkie dokumenty” i link na telefonie.
        $this->assertStringContainsString('class="kc-mega-docs"', $home);
        $this->assertStringContainsString('has-docs', $home);
        $this->assertStringContainsString('href="https://example.org/regulamin.pdf" target="_blank" rel="noopener"', $home);
        $this->assertStringContainsString('href="/o-nas#dokumenty" class="kc-mega-docs-all">Wszystkie dokumenty', $home);
        $this->assertStringContainsString('<li><a href="/o-nas#dokumenty" >Dokumenty</a></li>', str_replace('  ', ' ', $home));

        $second = KreatywniDocument::query()->where('title', 'Sprawozdanie')->sole();
        $this->post(route('admin.kreatywni-dokumenty.przesun', $second), ['direction' => 'up'])->assertRedirect();
        $html = $this->get('/o-nas')->getContent();
        $this->assertLessThan(strpos($html, 'Regulamin darowizn'), strpos($html, 'Sprawozdanie'));
        $this->assertStringContainsString('target="_blank" rel="noopener">Regulamin darowizn <span class="kc-sr"> (otwiera się w nowej karcie)</span>', $html);

        $this->put(route('admin.kreatywni-dokumenty.update', $second), ['title' => 'Sprawozdanie', 'url' => '/sprawozdanie', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('Sprawozdanie', $this->get('/o-nas')->getContent());

        $this->delete(route('admin.kreatywni-dokumenty.destroy', $second))->assertRedirect();
        $this->assertSame(['Regulamin darowizn'], KreatywniDocument::query()->pluck('title')->all());
    }

    public function test_walidacja_adresu_pliku_i_strony_innej_witryny(): void
    {
        $this->useTemplate();
        $this->actingAs($this->admin());
        $otherSite = SiteSetting::current()->replicate();
        $otherSite->forceFill(['slug' => 'inna-witryna', 'site_name' => 'Inna', 'domain' => null])->save();
        $foreign = Page::withoutGlobalScopes()->create(['site_id' => $otherSite->id, 'title' => 'Obca', 'slug' => 'obca', 'type' => 'standard', 'is_published' => true]);

        $this->post(route('admin.kreatywni-dokumenty.store'), ['title' => 'X', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->post(route('admin.kreatywni-dokumenty.store'), ['title' => 'X', 'file' => UploadedFile::fake()->create('wirus.exe', 10)])->assertSessionHasErrors('file');
        $this->post(route('admin.kreatywni-dokumenty.store'), ['title' => 'X', 'page_id' => $foreign->id])->assertSessionHasErrors('page_id');
        $this->post(route('admin.kreatywni-dokumenty.store'), ['title' => ''])->assertSessionHasErrors('title');
        $this->assertSame(0, KreatywniDocument::query()->count());
    }

    public function test_migracja_przenosi_dokumenty_witrynom_kreatywni(): void
    {
        $site = $this->useTemplate();
        $this->foundationDocs();
        $migration = require base_path('database/migrations/2026_11_23_130000_create_kreatywni_documents_table.php');

        $migration->up();
        $this->assertSame(3, KreatywniDocument::withoutGlobalScopes()->where('site_id', $site->id)->count());
        $this->assertNotNull(KreatywniDocument::query()->where('title', 'Statut Fundacji')->sole()->fileUrl(), 'Plik z załącznika skopiowany.');
        $migration->up();
        $this->assertSame(3, KreatywniDocument::query()->count(), 'Ponowne uruchomienie nie dubluje.');
    }

    public function test_dziala_przed_migracja_i_tylko_w_szablonie_kreatywni(): void
    {
        $this->useTemplate('default');
        $this->actingAs($this->admin())->get(route('admin.kreatywni-dokumenty.index'))->assertNotFound();

        $this->useTemplate();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.kreatywni-dokumenty.index'), false);

        Schema::drop('kreatywni_documents');
        \Illuminate\Support\Once::flush();
        $this->aboutPage();
        $this->get('/o-nas')->assertOk()->assertDontSee('id="dokumenty"', false);
        $this->get(route('admin.kreatywni-dokumenty.index'))->assertNotFound();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.kreatywni-dokumenty.index'), false);
    }

    public function test_dokument_innej_witryny_jest_niedostepny(): void
    {
        $this->useTemplate();
        $other = KreatywniDocument::withoutGlobalScopes()->create(['site_id' => 9999, 'title' => 'Obcy', 'order' => 1, 'is_active' => true]);

        $this->actingAs($this->admin())->get(route('admin.kreatywni-dokumenty.edit', $other))->assertNotFound();
    }

    public function test_blok_dokumentow_w_menu_o_nas_takze_przy_podpozycjach_z_panelu(): void
    {
        $this->useTemplate();
        $this->aboutPage();
        $about = NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Zespół', 'url' => '/o-nas#zespol', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1, 'parent_id' => $about->id]);
        KreatywniDocument::create(['title' => 'Statut Fundacji', 'order' => 1, 'is_active' => true]);
        KreatywniDocument::create(['title' => 'Ukryty dokument', 'order' => 2, 'is_active' => false]);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('class="kc-mega-docs"', $home);
        $this->assertStringContainsString('<span class="kc-mega-docs-name">Statut Fundacji</span>', $home);
        $this->assertStringContainsString('<span class="kc-mega-docs-meta">w przygotowaniu</span>', $home, 'Dokument bez pliku prowadzi do sekcji.');
        $this->assertStringNotContainsString('Ukryty dokument', $home);
        $this->assertStringContainsString('/o-nas#zespol', $home, 'Podpozycje z panelu zostają.');
    }
}
