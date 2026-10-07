<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feer_bands') && ! Schema::hasColumn('feer_bands', 'image_alt')) {
            Schema::table('feer_bands', function (Blueprint $table) {
                // Tekst alternatywny zdjęcia paska (puste = zdjęcie dekoracyjne).
                $table->string('image_alt', 255)->nullable()->after('button2_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('feer_bands', 'image_alt')) {
            Schema::table('feer_bands', fn (Blueprint $table) => $table->dropColumn('image_alt'));
        }
    }
};
