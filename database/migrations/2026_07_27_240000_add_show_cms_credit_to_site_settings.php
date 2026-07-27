<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Przełącznik podpisu CMS („weCMS · autor…") w stopce. */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('show_cms_credit')->default(true)->after('cookie_banner_text');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('show_cms_credit');
        });
    }
};
