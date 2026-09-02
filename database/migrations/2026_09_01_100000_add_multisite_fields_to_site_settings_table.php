<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Ścieżkowy identyfikator sub-witryny (np. "osrodek" dla /osrodek
            // i /site/osrodek). Główna witryna (id=1) ma slug puste.
            $table->string('slug')->nullable()->unique()->after('id');
            // Pełna domena/subdomena obsługująca sub-witrynę bez prefiksu
            // ścieżkowego (np. "pokrzywdzeni.krafos.pl").
            $table->string('domain')->nullable()->unique()->after('slug');
            // Wiersz federacji, do którego należy ta sub-witryna.
            $table->foreignId('parent_site_id')->nullable()->after('domain')
                ->constrained('site_settings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_site_id');
            $table->dropColumn(['slug', 'domain']);
        });
    }
};
