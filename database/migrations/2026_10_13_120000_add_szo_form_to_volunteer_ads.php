<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ogłoszenie wolontariatu może mieć formularz zgłoszeniowy z SZO (slug w SZO)
 * osadzony na stronie przez lustrzany formularz CMS (form_definition_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->string('szo_form_slug', 120)->nullable()->after('application_cta_label');
            $table->foreignId('form_definition_id')->nullable()->after('szo_form_slug')
                ->constrained('form_definitions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_definition_id');
            $table->dropColumn('szo_form_slug');
        });
    }
};
