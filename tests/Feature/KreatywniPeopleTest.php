<?php

namespace Tests\Feature;

use App\Models\KreatywniPerson;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mikro moduł „Osoby – Kreatywni”: panel (dodawanie ze zdjęciem, edycja, ukrywanie, kolejność, import z tekstów,
 * usuwanie), sekcja „Kto prowadzi warsztaty” na stronie „O nas” i Zespół na stronie głównej z modułu albo z tekstów,
 * działanie bez tabeli i tylko w szablonie „kreatywni”.
 */
class KreatywniPeopleTest extends TestCase
{
    use RefreshDatabase;

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

    private function aboutPage(): void
    {
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'content' => '']);
    }

    public function test_panel_dodaje_osobe_ze_zdjeciem_i_strona_o_nas_ja_pokazuje(): void
    {
        Storage::fake('public');
        $this->useTemplate();
        $this->aboutPage();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.kreatywni-osoby.index'))->assertOk()
            ->assertSee('Przenieś osoby z tekstów (2)')->assertSee('Osoby – Kreatywni');

        $this->post(route('admin.kreatywni-osoby.store'), [
            'name' => 'Ewa Nowak', 'role' => 'Plastyczka', 'bio' => "Pierwszy akapit.\n\nDrugi akapit.",
            'photo' => UploadedFile::fake()->image('ewa.jpg', 600, 600), 'photo_alt' => 'Ewa przy stole z farbami', 'is_active' => '1',
        ])->assertRedirect(route('admin.kreatywni-osoby.index'))->assertSessionHasNoErrors();

        $person = KreatywniPerson::query()->firstOrFail();
        $this->assertNotNull($person->photoUrl());

        $html = $this->get('/o-nas')->assertOk()->getContent();
        $this->assertStringContainsString('Ewa Nowak', $html);
        $this->assertStringContainsString('<p class="kc-about-role">Plastyczka</p>', $html);
        $this->assertStringContainsString('<p>Drugi akapit.</p>', $html);
        $this->assertStringContainsString('alt="Ewa przy stole z farbami" class="kc-avatar kc-avatar-lg"', $html);
        $this->assertStringNotContainsString('Anna Mossór-Koper', $html, 'Osoby z modułu zastępują osoby z tekstów.');
    }

    public function test_import_z_tekstow_ukrywanie_kolejnosc_i_usuwanie(): void
    {
        $this->useTemplate(extra: ['kreatywni_texts' => ['_hidden' => []]]);
        $this->aboutPage();
        $this->actingAs($this->admin());

        $this->post(route('admin.kreatywni-osoby.import'))->assertRedirect()->assertSessionHas('status');
        $this->assertSame(['Anna Mossór-Koper', 'Sławomir Koper'], KreatywniPerson::query()->orderBy('order')->pluck('name')->all());
        $this->post(route('admin.kreatywni-osoby.import'));
        $this->assertSame(2, KreatywniPerson::query()->count(), 'Drugi import nic nie dubluje.');

        $slawomir = KreatywniPerson::query()->where('name', 'Sławomir Koper')->first();
        $this->post(route('admin.kreatywni-osoby.przesun', $slawomir), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['Sławomir Koper', 'Anna Mossór-Koper'], KreatywniPerson::query()->orderBy('order')->pluck('name')->all());

        $anna = KreatywniPerson::query()->where('name', 'Anna Mossór-Koper')->first();
        $this->put(route('admin.kreatywni-osoby.update', $anna), ['name' => 'Anna Mossór-Koper', 'role' => 'Terapeutka', 'is_active' => '0'])
            ->assertSessionHasNoErrors();
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Sławomir Koper', $home, 'Sekcja Zespół na stronie głównej bierze osoby z modułu.');
        $this->assertStringNotContainsString('Terapeutka', $home, 'Ukryta osoba znika ze strony.');

        $this->delete(route('admin.kreatywni-osoby.destroy', $slawomir))->assertRedirect();
        $this->assertSame(['Anna Mossór-Koper'], KreatywniPerson::query()->pluck('name')->all());
    }

    public function test_bez_osob_w_module_strona_bierze_osoby_z_tekstow(): void
    {
        $this->useTemplate();
        $this->aboutPage();

        $this->get('/o-nas')->assertOk()->assertSee('Anna Mossór-Koper')->assertSee('kc-avatar kc-avatar-lg" aria-hidden="true">AM</span>', false);
    }

    public function test_dziala_przed_migracja_i_tylko_w_szablonie_kreatywni(): void
    {
        $this->useTemplate('default');
        $this->actingAs($this->admin())->get(route('admin.kreatywni-osoby.index'))->assertNotFound();

        $this->useTemplate();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.kreatywni-osoby.index'), false);

        Schema::drop('kreatywni_people');
        \Illuminate\Support\Once::flush();
        $this->aboutPage();
        $this->get('/o-nas')->assertOk()->assertSee('Anna Mossór-Koper');
        $this->get(route('admin.kreatywni-osoby.index'))->assertNotFound();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.kreatywni-osoby.index'), false);
    }

    public function test_osoba_innej_witryny_jest_niedostepna(): void
    {
        $this->useTemplate();
        $other = KreatywniPerson::withoutGlobalScopes()->create(['site_id' => 9999, 'name' => 'Obca osoba', 'order' => 1, 'is_active' => true]);

        $this->actingAs($this->admin())->get(route('admin.kreatywni-osoby.edit', $other))->assertNotFound();
    }
}
