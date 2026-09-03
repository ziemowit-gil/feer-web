<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->unsignedInteger('price_grosze')->nullable()->after('is_premium');
            $table->string('currency', 3)->default('PLN')->after('price_grosze');
        });
    }

    public function down(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->dropColumn(['price_grosze', 'currency']);
        });
    }
};
