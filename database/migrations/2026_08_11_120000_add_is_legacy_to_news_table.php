<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('is_archived');
        });
    }

    public function down(): void
    {
        Schema::table('news', fn (Blueprint $table) => $table->dropColumn('is_legacy'));
    }
};
