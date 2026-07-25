<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_address')->default('ul. Warszawska 12');
            $table->string('contact_city')->default('00-001 Warszawa');
            $table->string('contact_email')->default('fundacja@feer.org.pl');
            $table->string('contact_phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_address', 'contact_city', 'contact_email', 'contact_phone']);
        });
    }
};
