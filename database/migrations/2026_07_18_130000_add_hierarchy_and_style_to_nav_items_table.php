<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('nav_items')->cascadeOnDelete();
            $table->string('type')->default('link')->after('url');
            $table->string('module')->nullable()->after('type');
            $table->boolean('is_transparent_dropdown')->default(false)->after('is_button');
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['type', 'module', 'is_transparent_dropdown']);
        });
    }
};
