<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Szablon „kreatywni” (wizytówka fundacji): teksty strony głównej nadpisane w panelu (App\Support\KreatywniTexts).
 * Kod działa i bez tej kolumny — wtedy pokazuje teksty domyślne, a zapis ustawień ją pomija.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'kreatywni_texts')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('kreatywni_texts')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'kreatywni_texts')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('kreatywni_texts');
            });
        }
    }
};
