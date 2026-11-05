<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'banners')) {
            Schema::table('projects', function (Blueprint $table) {
                // {"banners": [id…], "zones": ["slug"…]} — banery i strefy z modułu Banery pokazywane na stronie działania.
                $table->json('banners')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'banners')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('banners');
            });
        }
    }
};
