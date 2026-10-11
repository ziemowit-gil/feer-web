<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Godziny, w których telefon osoby kontaktowej działania jest czynny (np. „w poniedziałki 15:00–17:00”). */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('coordinator_phone_hours', 120)->nullable()->after('coordinator_phone');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('coordinator_phone_hours');
        });
    }
};
