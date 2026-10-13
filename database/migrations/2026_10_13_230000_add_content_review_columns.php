<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Przegląd treści: unpublish_at — automatyczne zdjęcie z publikacji w wybranym dniu
 * (content:unpublish-due), reviewed_at — „sprawdzone, aktualne” bez zmiany treści
 * (przegląd treści nieaktualizowanych od wielu miesięcy).
 */
return new class extends Migration
{
    private const TABLES = ['pages', 'news', 'projects'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'unpublish_at')) {
                    $table->timestamp('unpublish_at')->nullable();
                }
                if (! Schema::hasColumn($name, 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['unpublish_at', 'reviewed_at']));
        }
    }
};
