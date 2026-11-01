<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Microsoft Clarity: ID projektu (kod ładuje się po zgodzie na cookies). */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'clarity_project_id')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->string('clarity_project_id', 32)->nullable()->after('matomo_cookieless');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'clarity_project_id')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('clarity_project_id'));
        }
    }
};
