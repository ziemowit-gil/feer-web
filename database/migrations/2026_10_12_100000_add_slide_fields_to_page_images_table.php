<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Zdjęcie z galerii strony jako slajd: tytuł, tekst i przycisk (jak slajdy strony głównej). */
    public function up(): void
    {
        Schema::table('page_images', function (Blueprint $table) {
            $table->string('title')->nullable()->after('caption');
            $table->string('text', 500)->nullable()->after('title');
            $table->string('button_label', 50)->nullable()->after('text');
            $table->string('button_url')->nullable()->after('button_label');
        });
    }

    public function down(): void
    {
        Schema::table('page_images', function (Blueprint $table) {
            $table->dropColumn(['title', 'text', 'button_label', 'button_url']);
        });
    }
};
