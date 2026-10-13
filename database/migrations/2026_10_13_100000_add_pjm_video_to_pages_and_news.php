<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tłumaczenie treści na Polski Język Migowy: adres wideo (YouTube, Vimeo albo plik MP4/WebM). */
    public function up(): void
    {
        foreach (['pages', 'news'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('pjm_video_url', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['pages', 'news'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('pjm_video_url'));
        }
    }
};
