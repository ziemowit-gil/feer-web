<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_actions', function (Blueprint $table) {
            $table->boolean('is_negative')->default(false)->after('color');
            $table->boolean('wide')->default(false)->after('is_negative');
        });
    }

    public function down(): void
    {
        Schema::table('quick_actions', function (Blueprint $table) {
            $table->dropColumn(['is_negative', 'wide']);
        });
    }
};
