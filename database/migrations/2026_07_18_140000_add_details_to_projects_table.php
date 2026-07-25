<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('for_whom')->nullable()->after('excerpt');
            $table->string('since')->nullable()->after('for_whom');
            $table->text('why')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['for_whom', 'since', 'why']);
        });
    }
};
