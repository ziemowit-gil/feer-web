<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Całość dla witryny w motywie FEER IS jednym poleceniem: treści (szkice stron), układ (szablon, zakładki, przyciski)
 * i wyłączenie niepotrzebnych modułów. Menu główne buduje osobny seeder, bo zastępuje istniejące menu:
 *
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsPortalSeeder --force
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsMenuSeeder --force
 */
class FeerIsPortalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([FeerIsPortalContentSeeder::class, FeerIsLayoutSeeder::class, FeerIsModulesSeeder::class]);

        $this->command?->line('Na koniec uruchom FeerIsMenuSeeder, żeby zbudować menu główne (zastępuje istniejące, kopia w storage/app/menu-backups).');
    }
}
