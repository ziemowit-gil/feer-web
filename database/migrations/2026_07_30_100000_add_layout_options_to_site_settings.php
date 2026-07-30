<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Opcje układu listy dla widoków publicznych: aktualności i wolontariat. */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('news_layout', 20)->default('grid')->after('news_default_image');
            $table->string('volunteer_layout', 20)->default('grid')->after('news_layout');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['news_layout', 'volunteer_layout']);
        });
    }
};
