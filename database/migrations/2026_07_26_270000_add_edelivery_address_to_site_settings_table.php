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
            // Adres do doręczeń elektronicznych (e-Doręczenia / ADE),
            // np. „AE:PL-12345-67890-ABCDE-12”. Pokazywany na /kontakt.
            $table->string('contact_edelivery_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('contact_edelivery_address');
        });
    }
};
