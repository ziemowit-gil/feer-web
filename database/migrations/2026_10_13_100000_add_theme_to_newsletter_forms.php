<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Wygląd formularza zapisu (kolory i ramki) edytowalny w panelu, z kontrolą kontrastu WCAG. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_forms', function (Blueprint $table) {
            $table->json('theme')->nullable()->after('accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_forms', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
