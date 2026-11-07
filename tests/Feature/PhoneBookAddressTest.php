<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Książka telefoniczna zawsze pod /nasze-numery-telefonow; dawny /ksiazka-telefoniczna przekierowuje. */
class PhoneBookAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_dawny_adres_przekierowuje_na_staly(): void
    {
        $this->get('/ksiazka-telefoniczna?dzial=Biuro')->assertStatus(301)->assertRedirect(url('/nasze-numery-telefonow').'?dzial=Biuro');
    }

    public function test_bez_stron_pokazuje_liste_z_modulu_a_bez_modulu_404(): void
    {
        PhoneBookEntry::create(['name' => 'Anna Kowalska', 'phone' => '601 350 487', 'is_active' => true]);
        $this->get('/nasze-numery-telefonow')->assertOk()->assertSee('Anna Kowalska');

        SiteSetting::current()->update(['disabled_modules' => ['phone_book']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/nasze-numery-telefonow')->assertNotFound();
    }

    public function test_glowna_strona_ksiazki_o_innym_adresie_jest_pod_stalym_adresem(): void
    {
        PhoneBookEntry::create(['name' => 'Anna Kowalska', 'group' => 'Zarząd', 'phone' => '601 350 487', 'is_active' => true]);
        Page::create(['title' => 'Telefony Zarządu', 'slug' => 'zarzad-tel', 'type' => 'contact_book', 'is_published' => true, 'type_data' => ['group' => 'Zarząd']]);
        Page::create(['title' => 'Numery telefonów', 'slug' => 'numery', 'type' => 'contact_book', 'is_published' => true]);

        // Strona działu (z filtrem) nie jest główną książką — pod stałym adresem jest strona bez filtra.
        $this->get('/nasze-numery-telefonow')->assertOk()->assertSee('Numery telefonów')->assertSee('Anna Kowalska');
    }

    public function test_migracja_nadaje_glownej_stronie_staly_adres_a_stary_przekierowuje(): void
    {
        $page = Page::create(['title' => 'Książka telefoniczna', 'slug' => 'telefony', 'type' => 'phone_book', 'is_published' => true]);

        (require base_path('database/migrations/2026_11_08_120000_move_phone_book_page_to_fixed_address.php'))->up();

        $this->assertSame('nasze-numery-telefonow', $page->fresh()->slug);
        $this->get('/telefony')->assertRedirect(url('/nasze-numery-telefonow'));
        $this->get('/nasze-numery-telefonow')->assertOk()->assertSee('Książka telefoniczna');
    }

    public function test_migracja_nie_rusza_zajetego_adresu(): void
    {
        Page::create(['title' => 'Inna strona', 'slug' => 'nasze-numery-telefonow', 'type' => 'standard', 'is_published' => true]);
        $book = Page::create(['title' => 'Książka', 'slug' => 'telefony', 'type' => 'contact_book', 'is_published' => true]);

        (require base_path('database/migrations/2026_11_08_120000_move_phone_book_page_to_fixed_address.php'))->up();

        $this->assertSame('telefony', $book->fresh()->slug);
    }

    public function test_nowa_strona_ksiazki_dostaje_tytul_ze_stalym_adresem(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.podstrony.create', ['type' => 'contact_book']))->assertOk()->assertSee('value="Nasze numery telefonów"', false);
    }
}
