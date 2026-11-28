<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FAQ warsztatu z oferty (/warsztat/{slug}): lista par pytanie–odpowiedź (JSON), pokazywana na podstronie
 * jako rozwijane pytania z danymi strukturalnymi FAQPage.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kreatywni_offer_items') && ! Schema::hasColumn('kreatywni_offer_items', 'faq')) {
            Schema::table('kreatywni_offer_items', function (Blueprint $table) {
                $table->json('faq')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kreatywni_offer_items') && Schema::hasColumn('kreatywni_offer_items', 'faq')) {
            Schema::table('kreatywni_offer_items', function (Blueprint $table) {
                $table->dropColumn('faq');
            });
        }
    }
};
