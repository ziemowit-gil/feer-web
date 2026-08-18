<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder do użytku wyłącznie poza środowiskiem produkcyjnym.
 *
 * Wywołuje DatabaseSeeder po sprawdzeniu APP_ENV.
 *
 *   php artisan db:seed --class=DemoSeeder
 *   php artisan migrate:fresh --seeder=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoSeeder nie może być uruchomiony w środowisku produkcyjnym (APP_ENV=production).'
        );

        $this->call(DatabaseSeeder::class);
    }
}
