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
            // A "harmonogram" page keeps its schedule as an ordered list of
            // entries (date / time / location / note / changed flag) plus an
            // optional banner used to inform visitors about a change.
            $table->json('schedule_items')->nullable()->after('event_registration_url');
            $table->text('schedule_change_notice')->nullable()->after('schedule_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['schedule_items', 'schedule_change_notice']);
        });
    }
};
