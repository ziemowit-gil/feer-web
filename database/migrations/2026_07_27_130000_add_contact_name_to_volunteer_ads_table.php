<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Osoba kontaktowa dla ogłoszenia o wolontariacie (opcjonalna). */
    public function up(): void
    {
        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('q_how_to_apply');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->dropColumn('contact_name');
        });
    }
};
