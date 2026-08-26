<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingsOfficeAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'FEER',
            'brand_color' => '#c31432',
            'header_layout' => 'classic',
            'content_editor' => 'tinymce',
            'mail_transport' => 'default',
            'contact_address' => 'Władysława Barbackiego 28/18',
            'contact_city' => '33-300 Nowy Sącz',
            'contact_email' => 'fundacja@feer.org.pl',
        ], $overrides);
    }

    public function test_office_address_fields_and_photo_are_saved(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), $this->payload([
                'contact_office_address' => 'ul. Przykładowa 10/3',
                'contact_office_city' => '30-001 Kraków',
                'contact_office_building' => 'Biurowiec HEXAGON',
                'contact_office_note' => 'Biuro dzielimy z HubKolektyw sp. z o.o.',
                'contact_office_photo_alt' => 'Wejście do biurowca HEXAGON',
                'office_photo' => UploadedFile::fake()->image('biuro.jpg', 800, 600),
            ]))
            ->assertRedirect();

        $settings = SiteSetting::query()->first();

        $this->assertSame('Biurowiec HEXAGON', $settings->contact_office_building);
        $this->assertSame('ul. Przykładowa 10/3', $settings->contact_office_address);
        $this->assertNotNull($settings->officePhotoUrl(), 'Zdjęcie biura powinno trafić do kolekcji office_photo.');
    }

    public function test_contact_page_splits_registered_and_office_address(): void
    {
        SiteSetting::current()->update([
            'contact_office_address' => 'ul. Przykładowa 10/3',
            'contact_office_city' => '30-001 Kraków',
            'contact_office_building' => 'Biurowiec HEXAGON',
            'contact_office_note' => 'Biuro dzielimy z HubKolektyw sp. z o.o. — tej nazwy szukaj na domofonie.',
        ]);

        $this->get('/kontakt')->assertOk()
            ->assertSee('Adres rejestrowy')
            ->assertSee('Biuro / korespondencja')
            ->assertSee('Biurowiec HEXAGON')
            ->assertSee('HubKolektyw sp. z o.o.', false);
    }

    public function test_without_office_address_the_page_shows_one_plain_address(): void
    {
        $this->get('/kontakt')->assertOk()
            ->assertSee('Adres')
            ->assertDontSee('Adres rejestrowy')
            ->assertDontSee('Biuro / korespondencja');
    }
}
