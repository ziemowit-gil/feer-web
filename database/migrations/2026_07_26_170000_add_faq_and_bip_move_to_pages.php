<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Typ „FAQ” — wstęp + lista par pytanie/odpowiedź (akordeon).
            $table->text('faq_intro')->nullable()->after('about_section_order');
            $table->json('faq_items')->nullable()->after('faq_intro');

            // Typ „Bip-Move” — komunikat o przeniesieniu treści do BIP.
            $table->string('bip_move_url')->nullable()->after('faq_items');
            $table->text('bip_move_note')->nullable()->after('bip_move_url');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['faq_intro', 'faq_items', 'bip_move_url', 'bip_move_note']);
        });
    }
};
