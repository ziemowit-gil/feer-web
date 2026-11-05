<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('banner_zones')->where('slug', 'breadcrumbs')->doesntExist()) {
            DB::table('banner_zones')->insert([
                'slug' => 'breadcrumbs', 'label' => 'Pod okruszkami',
                'description' => 'Baner na podstronach, bezpośrednio pod ścieżką nawigacji (okruszkami), nad treścią strony. Zalecany rozmiar banera: 1200 × 120 px.',
                'max_concurrent' => 3, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('banner_zones')->where('slug', 'breadcrumbs')->delete();
    }
};
