<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gałęzie stron przypisane osobie (jak „DB mounts” w TYPO3): redaktor widzi i edytuje w module Strony tylko te strony
 * z podstronami. Działa obok gałęzi z grupy (user_groups.page_ids) — oba zbiory się sumują.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'page_mounts')) {
            Schema::table('users', fn (Blueprint $table) => $table->json('page_mounts')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'page_mounts')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('page_mounts'));
        }
    }
};
