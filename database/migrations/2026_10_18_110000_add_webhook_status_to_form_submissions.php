<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wysyłka zgłoszeń z formularzy na zewnętrzne API (webhook): wynik ostatniej próby.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('form_submissions', 'webhook_sent_at')) {
                $table->timestamp('webhook_sent_at')->nullable();
            }
            if (! Schema::hasColumn('form_submissions', 'webhook_error')) {
                $table->string('webhook_error', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            foreach (['webhook_sent_at', 'webhook_error'] as $col) {
                if (Schema::hasColumn('form_submissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
