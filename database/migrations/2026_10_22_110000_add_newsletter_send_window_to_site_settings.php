<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Okno wysyłki newslettera (dni tygodnia + godziny) — JSON czytany przez Modules\Newsletter\Services\SendWindow. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'newsletter_send_window')) {
            Schema::table('site_settings', fn (Blueprint $t) => $t->json('newsletter_send_window')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'newsletter_send_window')) {
            Schema::table('site_settings', fn (Blueprint $t) => $t->dropColumn('newsletter_send_window'));
        }
    }
};
