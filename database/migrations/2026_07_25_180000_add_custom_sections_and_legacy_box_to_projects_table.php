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
        Schema::table('projects', function (Blueprint $table) {
            $table->json('custom_sections')->nullable()->after('outcomes');
            $table->boolean('show_legacy_box')->default(false)->after('custom_sections');
            $table->string('legacy_url')->nullable()->after('show_legacy_box');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['custom_sections', 'show_legacy_box', 'legacy_url']);
        });
    }
};
