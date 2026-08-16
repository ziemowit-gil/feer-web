<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_actions', function (Blueprint $table) {
            // Liczba kolumn: 1 (domyślny), 2 (pół), 3 (cały rząd).
            $table->tinyInteger('cols')->default(1)->after('is_negative');
            // Układ paskowy: ikona + tekst w jednej linii zamiast karty.
            $table->boolean('strip')->default(false)->after('cols');
        });

        // Przenieś dane z kolumny `wide` do `cols`.
        DB::table('quick_actions')->where('wide', true)->update(['cols' => 3]);

        Schema::table('quick_actions', function (Blueprint $table) {
            $table->dropColumn('wide');
        });
    }

    public function down(): void
    {
        Schema::table('quick_actions', function (Blueprint $table) {
            $table->boolean('wide')->default(false)->after('is_negative');
        });

        DB::table('quick_actions')->where('cols', 3)->update(['wide' => true]);

        Schema::table('quick_actions', function (Blueprint $table) {
            $table->dropColumn(['cols', 'strip']);
        });
    }
};
