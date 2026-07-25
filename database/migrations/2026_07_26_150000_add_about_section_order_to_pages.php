<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolejność sekcji na stronie typu "O organizacji", ustawialna w panelu.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('about_section_order')->nullable()->after('about_team');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('about_section_order');
        });
    }
};
