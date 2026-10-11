<?php

namespace Tests\Feature;

use App\Support\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Donation;
use App\Models\SiteSetting;
use Tests\TestCase;

class EditionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Edition::flush();
    }

    protected function tearDown(): void
    {
        Edition::flush();

        parent::tearDown();
    }

    private function useEdition(?string $forced, string $appUrl = 'https://klient.example.org', string $key = ''): void
    {
        config([
            'app.url' => $appUrl,
            'editions.forced' => $forced,
            'editions.license_key' => $key,
        ]);
        Edition::flush();
    }

    public function test_feer_org_pl_is_always_pro_even_when_forced_free(): void
    {
        $this->useEdition('free', 'https://feer.org.pl');
        $this->assertTrue(Edition::isPro());

        $this->useEdition('free', 'https://wiem.feer.org.pl');
        $this->assertTrue(Edition::isPro());
    }

    public function test_local_environment_is_pro(): void
    {
        $this->useEdition(null, 'http://localhost');

        $this->assertTrue(Edition::isPro());
    }

    public function test_installation_without_license_key_is_free(): void
    {
        $this->useEdition(null);

        $this->assertFalse(Edition::isPro());
        $this->assertFalse(Edition::allows('newsletter'));
        $this->assertTrue(Edition::allows('news'), 'funkcje spoza listy są darmowe');
        $this->assertFalse(Edition::allowsTemplate('municipality'));
        $this->assertTrue(Edition::allowsTemplate('ngo'));
    }

    public function test_unverifiable_license_key_does_not_unlock_pro(): void
    {
        config(['license.public_key' => null]);
        $this->useEdition(null, key: 'WCMS-COKOLWIEK');

        $this->assertFalse(Edition::isPro());
    }

    /** Darowizny (edition:donations) — strona widoczna, wysłanie formularza zablokowane. */
    public function test_free_edition_keeps_paid_feature_readable_but_blocks_writes(): void
    {
        $this->useEdition('free', 'http://localhost');
        SiteSetting::current()->forceFill(['donation_amounts' => '30, 60'])->save();

        $this->get(route('donation.show'))->assertOk();

        $this->from(route('donation.show'))->post(route('donation.store'), ['amount' => '60'])
            ->assertRedirect(route('donation.show'))
            ->assertSessionHasErrors('edition');

        $this->assertSame(0, Donation::count());
    }

    public function test_pro_edition_passes_writes_to_the_controller(): void
    {
        $this->useEdition('pro', 'http://localhost');
        SiteSetting::current()->forceFill(['donation_amounts' => '30, 60'])->save();

        $this->post(route('donation.store'), ['amount' => '60'])
            ->assertSessionHasErrors('first_name')
            ->assertSessionDoesntHaveErrors('edition');
    }
}
