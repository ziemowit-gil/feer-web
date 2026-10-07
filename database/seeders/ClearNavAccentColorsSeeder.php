<?php

namespace Database\Seeders;

use App\Models\NavItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Usuwa kolory pozycji menu („Kolor pozycji”) — wszystkie wracają do domyślnego wyglądu.
 * Kolor jest opcjonalny; po tym seederze ustawia go ponownie redaktor w panelu. Idempotentny.
 *
 * Uruchomienie: php artisan db:seed --class=ClearNavAccentColorsSeeder --force
 */
class ClearNavAccentColorsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('nav_items', 'accent_color')) {
            return;
        }

        NavItem::whereNotNull('accent_color')->update(['accent_color' => null]);
    }
}
