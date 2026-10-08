<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // „Kafelki na stronie": włącza sekcję kafelków na stronach innych niż „Siatka kafelków" (ta zawsze je pokazuje).
            $table->boolean('tiles_enabled')->default(false);
        });

        // Strony, które już mają zapisane kafelki, zostają z nimi widoczne.
        \Illuminate\Support\Facades\DB::table('pages')->whereNotNull('tiles')->where('tiles', '!=', '[]')->update(['tiles_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('tiles_enabled'));
    }
};
