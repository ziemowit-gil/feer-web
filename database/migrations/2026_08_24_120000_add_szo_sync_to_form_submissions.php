<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ślad przekazania zgłoszenia do SZO.
 *
 * Bez tych trzech kolumn nie da się odpowiedzieć na pytanie „czy to zgłoszenie
 * dotarło do CRM-a", a przy integracji przez sieć to jest pierwsze pytanie,
 * jakie ktoś zada. `szo_synced_at` = null przy niepustym `szo_error` oznacza
 * zgłoszenie do ponowienia (polecenie szo:push-submissions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('szo_contact_id')->nullable()->after('read_at');
            $table->timestamp('szo_synced_at')->nullable()->after('szo_contact_id');
            $table->string('szo_error', 500)->nullable()->after('szo_synced_at');

            $table->index('szo_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['szo_synced_at']);
            $table->dropColumn(['szo_contact_id', 'szo_synced_at', 'szo_error']);
        });
    }
};
