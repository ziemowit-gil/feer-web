<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\ContactWordingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactWordingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_zamienia_ogolny_rachunek_na_glowny_i_jest_idempotentny(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update([
            'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Rachunek ogólny do wpłat w ramach działalności odpłatnej']],
            'contact_bank_accounts_note' => 'Prosimy wpłacać na ogólny rachunek bankowy.',
        ]);

        $this->seed(ContactWordingSeeder::class);
        $this->seed(ContactWordingSeeder::class);

        $s = SiteSetting::first();
        $this->assertSame('Rachunek główny do wpłat w ramach działalności odpłatnej', $s->contact_bank_accounts[0]['purpose']);
        $this->assertSame('Prosimy wpłacać na główny rachunek bankowy.', $s->contact_bank_accounts_note);
    }
}
