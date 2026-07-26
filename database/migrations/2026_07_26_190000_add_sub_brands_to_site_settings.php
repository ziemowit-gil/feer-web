<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nazwane kolory submarek (np. „NGO”, „Seniorzy”) do wskazywania per treść.
        // Format: [{"name": "...", "color": "#rrggbb"}]
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('sub_brands')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $t) => $t->dropColumn('sub_brands'));
    }
};
