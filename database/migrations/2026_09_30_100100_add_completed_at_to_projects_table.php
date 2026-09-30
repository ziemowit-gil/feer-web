<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data zakończenia projektu — pozwala dzielić archiwum „To już zrobiliśmy"
     * na okresy (np. zrealizowane przed / po 1 marca 2026) linkami
     * /projekty/archiwum?przed=… i ?po=….
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('completed_at')->nullable()->after('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
