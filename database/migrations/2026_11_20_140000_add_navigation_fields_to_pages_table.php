<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Strony jak w TYPO3: tytuł w nawigacji (krótka nazwa w menu i okruszkach, `nav_title`)
 * i „Ukryj w menu” niezależne od publikacji (`hide_in_menu`, jak nav_hide).
 * Kod działa też przed migracją (Page::hasNavigationColumns()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'nav_title')) {
                $table->string('nav_title', 120)->nullable()->after('title');
            }
            if (! Schema::hasColumn('pages', 'hide_in_menu')) {
                $table->boolean('hide_in_menu')->default(false)->after('show_in_menu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            foreach (['nav_title', 'hide_in_menu'] as $column) {
                if (Schema::hasColumn('pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
