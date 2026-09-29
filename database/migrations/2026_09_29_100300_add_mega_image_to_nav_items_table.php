<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Grafika promocyjna w kolumnie bocznej mega menu (adres + tekst alternatywny). */
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->string('mega_image', 500)->nullable()->after('description');
            $table->string('mega_image_alt')->nullable()->after('mega_image');
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn(['mega_image', 'mega_image_alt']);
        });
    }
};
