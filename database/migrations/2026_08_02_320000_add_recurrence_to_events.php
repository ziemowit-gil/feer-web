<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Powtarzanie wydarzeń: rodzaj cyklu, data zakończenia serii i wskaźnik
 * do rekordu-rodzica. Instancje (dzieci) mają ustawione recurrence_parent_id;
 * rekord-rodzic ma recurrence_type != null i recurrence_parent_id = null.
 * Usunięcie rodzica ustawia null na dzieciach (nie kaskaduje usunięcia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('recurrence_type', 20)->nullable()->after('archived_at');
            $table->date('recurrence_ends_at')->nullable()->after('recurrence_type');
            $table->unsignedBigInteger('recurrence_parent_id')->nullable()->after('recurrence_ends_at');

            $table->foreign('recurrence_parent_id')
                ->references('id')
                ->on('events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn(['recurrence_type', 'recurrence_ends_at', 'recurrence_parent_id']);
        });
    }
};
