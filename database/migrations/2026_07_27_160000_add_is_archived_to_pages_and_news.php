<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * „Archiwum": treść oznaczona jako archiwalna pozostaje opublikowana
     * (i przeszukiwalna), ale na stronie pokazuje komunikat, że powstała dawno
     * temu i może być nieaktualna.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_published');
        });
        Schema::table('news', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('is_archived'));
        Schema::table('news', fn (Blueprint $table) => $table->dropColumn('is_archived'));
    }
};
