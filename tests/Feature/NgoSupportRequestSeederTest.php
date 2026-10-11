<?php

namespace Tests\Feature;

use App\Models\NgoSupportRequest;
use Database\Seeders\NgoSupportRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NgoSupportRequestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_tworzy_10_zgloszen_z_roznymi_statusami_i_jest_powtarzalny(): void
    {
        $this->seed(NgoSupportRequestSeeder::class);
        $this->seed(NgoSupportRequestSeeder::class);

        $this->assertSame(10, NgoSupportRequest::count());
        $this->assertEqualsCanonicalizing(array_keys(NgoSupportRequest::STATUSES), NgoSupportRequest::distinct()->pluck('status')->all());

        foreach (NgoSupportRequest::all() as $r) {
            $this->assertTrue($r->privacy_consent);
            $this->assertIsArray($r->support_areas);
            $this->assertNotEmpty($r->support_areas);
            $this->assertEmpty(array_diff($r->support_areas, NgoSupportRequest::SUPPORT_AREAS));
            $this->assertContains($r->role, NgoSupportRequest::ROLES);
            $this->assertArrayHasKey($r->preferred_contact_channel, NgoSupportRequest::CONTACT_CHANNELS);
            $this->assertNotEmpty($r->description);
            $this->assertMatchesRegularExpression('/^[^@\s]+@[^@\s]+\.[a-z]+$/', $r->email);
        }
    }

    public function test_domyslny_status_nowego_zgloszenia_to_new(): void
    {
        $r = NgoSupportRequest::create([
            'name' => 'Anna Nowak', 'organization_name' => 'Fundacja Test', 'role' => 'Zarząd', 'email' => 'anna@example.org',
            'support_areas' => ['Inne'], 'description' => 'Opis', 'privacy_consent' => true,
        ]);

        $this->assertSame('new', $r->fresh()->status);
        $this->assertSame('Nowe', $r->statusLabel());
        $this->assertSame('E-mail', $r->contactChannelLabel());
    }
}
