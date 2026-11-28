<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO podstron warsztatów z oferty (/warsztat/{slug}): własny tytuł i opis dla wyszukiwarek i podglądu linku.
 * Puste = tytuł z nazwy warsztatu, opis z krótkiego opisu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kreatywni_offer_items')) {
            return;
        }
        Schema::table('kreatywni_offer_items', function (Blueprint $table) {
            if (! Schema::hasColumn('kreatywni_offer_items', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (! Schema::hasColumn('kreatywni_offer_items', 'meta_description')) {
                $table->string('meta_description', 300)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kreatywni_offer_items')) {
            return;
        }
        Schema::table('kreatywni_offer_items', function (Blueprint $table) {
            foreach (['meta_title', 'meta_description'] as $column) {
                if (Schema::hasColumn('kreatywni_offer_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
