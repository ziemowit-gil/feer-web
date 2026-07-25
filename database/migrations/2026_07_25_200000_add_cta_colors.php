<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->string('button_color')->nullable();
        });

        Schema::table('quick_actions', function (Blueprint $table) {
            $table->string('color')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn('button_color');
        });

        Schema::table('quick_actions', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
