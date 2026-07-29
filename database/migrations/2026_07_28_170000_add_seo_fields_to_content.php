<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pages', 'news', 'projects'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('meta_title')->nullable();
                $t->string('meta_description', 300)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['pages', 'news', 'projects'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['meta_title', 'meta_description']));
        }
    }
};
