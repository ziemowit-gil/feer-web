<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\ContactOfficeHoursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Seeder godzin pracy biura i informacji o obsłudze biurowej (HubKolektyw). */
class ContactOfficeHoursSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_seeder_zmienia_godziny_i_dopisuje_informacje(): void
    {
        SiteSetting::current()->update(['contact_office_hours' => ContactOfficeHoursSeeder::OLD_HOURS, 'contact_office_note' => null]);

        $this->seed(ContactOfficeHoursSeeder::class);

        $s = SiteSetting::first();
        $this->assertSame('Od poniedziałku do środy, 9:00 do 16:00', $s->contact_office_hours);
        $this->assertStringContainsString('HubKolektyw', $s->contact_office_note);
        $this->assertStringContainsString('pracujemy zdalnie', $s->contact_office_note);
    }

    public function test_seeder_nie_nadpisuje_zmian_z_panelu(): void
    {
        SiteSetting::current()->update(['contact_office_hours' => 'Pon-Pt 8-10', 'contact_office_note' => 'Moja uwaga']);

        $this->seed(ContactOfficeHoursSeeder::class);

        $s = SiteSetting::first();
        $this->assertSame('Pon-Pt 8-10', $s->contact_office_hours);
        $this->assertSame('Moja uwaga', $s->contact_office_note);
    }

    public function test_strona_kontaktu_feer_pokazuje_informacje_przy_godzinach(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'contact_layout' => 'tabs', 'contact_office_hours' => ContactOfficeHoursSeeder::OLD_HOURS, 'contact_office_note' => null]);
        $this->seed(ContactOfficeHoursSeeder::class);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/kontakt')->assertOk()->assertSee('Godziny pracy biura')->assertSee('9:00')->assertSee('HubKolektyw');
    }
}
