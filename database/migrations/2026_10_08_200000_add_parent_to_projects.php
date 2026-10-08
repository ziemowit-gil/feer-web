<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Podprojekty (np. wersja płatna i bezpłatna szkolenia): projekt nadrzędny + lista dziedziczonych pól.
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->json('inherit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['parent_id', 'inherit']));
    }
};
