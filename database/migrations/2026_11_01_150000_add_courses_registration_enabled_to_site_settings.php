<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Przełącznik zapisów online na szkolenia (moduł „Szkolenia i zajęcia”). Null = włączone (jak dotąd). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'courses_registration_enabled')) {
                $table->boolean('courses_registration_enabled')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'courses_registration_enabled')) {
                $table->dropColumn('courses_registration_enabled');
            }
        });
    }
};
