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
            // Lista rachunków bankowych pokazywanych na podstronie /kontakt.
            // Każdy wpis to para: numer konta oraz opis, do czego służy
            // (co można na nie wpłacić). Odrębna od pojedynczych pól
            // bank_account_number / bank_account_tax_number używanych na /wsparcie.
            $table->json('contact_bank_accounts')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('contact_bank_accounts');
        });
    }
};
