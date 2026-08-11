<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false)->after('is_archival');
        });
    }

    public function down(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->dropColumn('is_premium');
        });
    }
};
