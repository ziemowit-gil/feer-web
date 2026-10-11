<?php

namespace Database\Seeders;

use App\Models\NgoSupportRequest;
use Database\Factories\NgoSupportRequestFactory;
use Illuminate\Database\Seeder;

/**
 * 10 przykładowych zgłoszeń „Wsparcie technologiczne dla NGO” z różnymi statusami.
 * Uruchom: php artisan db:seed --class=NgoSupportRequestSeeder (na serwerze: php84 artisan …).
 * Przy ponownym uruchomieniu najpierw usuwa poprzednie przykładowe zgłoszenia (organizacje z listy fabryki),
 * żeby nie mnożyć danych testowych.
 */
class NgoSupportRequestSeeder extends Seeder
{
    /** Rozkład statusów: najwięcej nowych, po kilka w toku, pojedyncze zamknięte. */
    private const STATUS_PLAN = [
        'new'         => 3,
        'in_progress' => 2,
        'contacted'   => 2,
        'completed'   => 2,
        'archived'    => 1,
    ];

    public function run(): void
    {
        NgoSupportRequest::query()
            ->whereIn('organization_name', array_column(NgoSupportRequestFactory::ORGANIZATIONS, 0))
            ->delete();

        foreach (self::STATUS_PLAN as $status => $count) {
            NgoSupportRequest::factory()->count($count)->status($status)->create();
        }

        $this->command?->info('Utworzono '.array_sum(self::STATUS_PLAN).' przykładowych zgłoszeń wsparcia technologicznego NGO.');
    }
}
