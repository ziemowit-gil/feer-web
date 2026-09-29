<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Układ sekcji „Numery rachunków bankowych" na stronie kontaktowej (SiteSetting::BANK_ACCOUNTS_LAYOUTS). */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_bank_accounts_layout', 20)->default('cards')->after('contact_bank_accounts_note');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('contact_bank_accounts_layout');
        });
    }
};
