<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wydarzenia i oferty pracy — jak oferty wolontariatu — mogą mieć formularz zgłoszeń z SZO
 * (slug w SZO) osadzony na stronie przez lustrzany formularz CMS (form_definition_id).
 */
return new class extends Migration
{
    private const TABLES = ['events', 'job_offers'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('szo_form_slug', 120)->nullable();
                $table->foreignId('form_definition_id')->nullable()->constrained('form_definitions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('form_definition_id');
                $table->dropColumn('szo_form_slug');
            });
        }
    }
};
