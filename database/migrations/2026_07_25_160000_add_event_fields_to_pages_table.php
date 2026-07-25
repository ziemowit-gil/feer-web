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
            $table->string('type')->default('standard')->after('slug');
            $table->string('event_mode')->nullable()->after('type');
            $table->string('event_when')->nullable()->after('event_mode');
            $table->string('event_location')->nullable()->after('event_when');
            $table->text('event_how_to_join')->nullable()->after('event_location');
            $table->string('event_registration_url')->nullable()->after('event_how_to_join');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'event_mode', 'event_when', 'event_location', 'event_how_to_join', 'event_registration_url',
            ]);
        });
    }
};
