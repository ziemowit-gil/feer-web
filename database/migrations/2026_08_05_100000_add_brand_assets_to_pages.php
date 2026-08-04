<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('brand_brandbook_url')->nullable()->after('content');
            $table->json('brand_sections')->nullable()->after('brand_brandbook_url');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['brand_brandbook_url', 'brand_sections']);
        });
    }
};
