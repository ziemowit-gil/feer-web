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
            // How a project subpage is surfaced on its project's page:
            // "link" (only a link in the list), "tab" (a tab), or "inline"
            // (a section in the project body). It always keeps its own URL.
            $table->string('project_display')->default('link')->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('project_display');
        });
    }
};
