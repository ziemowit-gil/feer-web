<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // When set, the schedule page shows a "not published yet" notice
            // instead of the timetable — useful while the dates are being set.
            $table->boolean('schedule_pending')->default(false)->after('schedule_change_notice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('schedule_pending');
        });
    }
};
