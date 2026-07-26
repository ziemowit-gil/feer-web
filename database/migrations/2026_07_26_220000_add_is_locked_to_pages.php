<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Blokada edycji treści przez innych — ustawia/zdejmuje wyłącznie admin.
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn('is_locked'));
    }
};
