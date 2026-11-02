<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ramka z komunikatem na stronie głównej i w kontakcie (Ustawienia → Serwis). */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'notice_enabled')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->boolean('notice_enabled')->default(false);
                $table->string('notice_title')->nullable();
                $table->text('notice_text')->nullable();
                $table->string('notice_style', 20)->default('info');
                $table->boolean('notice_on_home')->default(true);
                $table->boolean('notice_on_contact')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'notice_enabled')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['notice_enabled', 'notice_title', 'notice_text', 'notice_style', 'notice_on_home', 'notice_on_contact']));
        }
    }
};
