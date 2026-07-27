<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pola subtypu „Wewnętrzna: Panel współpracownika": duży obraz hero, krótki
     * wstęp oraz zbiór kafelków-linków do systemów dla współpracowników.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('hub_hero')->nullable()->after('access_password');
            $table->text('hub_intro')->nullable()->after('hub_hero');
            $table->json('hub_links')->nullable()->after('hub_intro'); // [{label, url, description, icon}]
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['hub_hero', 'hub_intro', 'hub_links']);
        });
    }
};
