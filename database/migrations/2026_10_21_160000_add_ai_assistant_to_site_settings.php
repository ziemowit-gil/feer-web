<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Asystent AI (Claude API) przy pisaniu tekstów w panelu: włącznik, klucz API (szyfrowany) i model. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false);
            $table->text('ai_api_key')->nullable();
            $table->string('ai_model', 60)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['ai_enabled', 'ai_api_key', 'ai_model']));
    }
};
