<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Szablon strony działania do wyboru w panelu: classic (panel boczny po prawej, jak dotąd), left (panel po lewej)
 * albo single (jedna kolumna, panel pod treścią). Różnią się układem siatki, treść jest ta sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'layout_template')) {
            return;
        }

        Schema::table('projects', fn (Blueprint $table) => $table->string('layout_template', 12)->default('classic'));
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'layout_template')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('layout_template'));
        }
    }
};
