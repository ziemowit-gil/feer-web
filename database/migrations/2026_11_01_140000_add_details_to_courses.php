<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rozbudowa oferty „Szkolenia i zajęcia”: trener (z bazy prowadzących, ze zdjęciem i biogramem), program, efekty, dla kogo,
 * wymagania, czas trwania, certyfikat, uwaga do ceny, warunki udziału, FAQ, dofinansowanie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach ([
                ['facilitator_id', fn () => $table->unsignedBigInteger('facilitator_id')->nullable()->index()],
                ['duration', fn () => $table->string('duration', 120)->nullable()],
                ['level', fn () => $table->string('level', 60)->nullable()],
                ['certificate', fn () => $table->string('certificate', 200)->nullable()],
                ['price_note', fn () => $table->string('price_note', 300)->nullable()],
                ['funding', fn () => $table->string('funding', 300)->nullable()],
                ['audience', fn () => $table->text('audience')->nullable()],
                ['outcomes', fn () => $table->text('outcomes')->nullable()],
                ['program', fn () => $table->text('program')->nullable()],
                ['prerequisites', fn () => $table->text('prerequisites')->nullable()],
                ['terms', fn () => $table->longText('terms')->nullable()],
                ['faq', fn () => $table->text('faq')->nullable()],
            ] as [$column, $add]) {
                if (! Schema::hasColumn('courses', $column)) {
                    $add();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach (['facilitator_id', 'duration', 'level', 'certificate', 'price_note', 'funding', 'audience', 'outcomes', 'program', 'prerequisites', 'terms', 'faq'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
