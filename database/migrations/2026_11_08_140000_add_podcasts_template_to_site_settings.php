<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dedykowany wygląd podstrony „Podcasty” (/podcasty) — szablon nadpisujący szablon witryny tylko dla tej podstrony. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'podcasts_template')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->string('podcasts_template', 40)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'podcasts_template')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('podcasts_template');
            });
        }
    }
};
