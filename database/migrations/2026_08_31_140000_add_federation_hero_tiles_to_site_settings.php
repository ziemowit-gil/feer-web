<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edytowalne kafelki bloku hero na stronie głównej szablonu "federation"
 * (jedyny szablon, w którym ten układ istnieje).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('federation_hero_tiles')->nullable()->after('blocked_options');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('federation_hero_tiles');
        });
    }
};
