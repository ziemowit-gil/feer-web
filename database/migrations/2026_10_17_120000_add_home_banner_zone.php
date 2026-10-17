<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Strefa banerów „Strona główna” — lista banerów pod skrótami na stronie głównej (szablon FEER). */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('banner_zones')->where('slug', 'home')->doesntExist()) {
            DB::table('banner_zones')->insert([
                'slug' => 'home', 'label' => 'Strona główna (pod skrótami)',
                'description' => 'Lista banerów graficznych na stronie głównej, pod sekcją skrótów. Zalecany rozmiar banera: 1200 × 120 px.',
                'max_concurrent' => 6, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('banner_zones')->where('slug', 'home')->delete();
    }
};
