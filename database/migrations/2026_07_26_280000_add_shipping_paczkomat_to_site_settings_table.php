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
            // Informacja o nadawaniu przesyłek (paczka / list), w tym paczkomat InPost.
            $table->string('contact_shipping_note')->nullable();
            $table->string('contact_paczkomat_code')->nullable();
            $table->string('contact_paczkomat_address')->nullable();
            $table->string('contact_paczkomat_location')->nullable();
            $table->string('contact_shipping_phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_shipping_note',
                'contact_paczkomat_code',
                'contact_paczkomat_address',
                'contact_paczkomat_location',
                'contact_shipping_phone',
            ]);
        });
    }
};
