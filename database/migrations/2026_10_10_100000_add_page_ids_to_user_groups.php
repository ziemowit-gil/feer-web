<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Uprawnienia grupy do wybranych stron/działów: lista stron (wraz z ich podstronami), które redaktor może edytować. */
    public function up(): void
    {
        Schema::table('user_groups', fn (Blueprint $table) => $table->json('page_ids')->nullable()->after('project_category_ids'));
    }

    public function down(): void
    {
        Schema::table('user_groups', fn (Blueprint $table) => $table->dropColumn('page_ids'));
    }
};
