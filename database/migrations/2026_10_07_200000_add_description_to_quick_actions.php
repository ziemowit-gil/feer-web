<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('quick_actions', 'description')) {
            return;
        }

        Schema::table('quick_actions', function (Blueprint $table) {
            // Krótki opis pod nazwą kafla („mikropis").
            $table->string('description', 140)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('quick_actions', 'description')) {
            Schema::table('quick_actions', fn (Blueprint $table) => $table->dropColumn('description'));
        }
    }
};
