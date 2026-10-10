<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Elementy landing page przy formularzu zapisu: korzyści, pytania i odpowiedzi, dowód społeczny. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_forms', function (Blueprint $table) {
            $table->json('benefits')->nullable()->after('lead');
            $table->json('faq')->nullable()->after('benefits');
            $table->string('proof', 200)->nullable()->after('faq');
        });

        // Domyślny formularz dostaje korzyści i pytania wynikające z treści strony (bez wymyślonych liczb).
        DB::table('newsletter_forms')->where('is_default', true)->whereNull('benefits')->update([
            'benefits' => json_encode([
                'Aktualności z działań Fundacji FEER',
                'Zaproszenia na szkolenia i wydarzenia',
                'Materiały edukacyjne do wykorzystania w pracy',
                'Treści w wersji Łatwy Odczyt (ETR)',
            ], JSON_UNESCAPED_UNICODE),
            'faq' => json_encode([
                ['q' => 'Jak często wysyłacie newsletter?', 'a' => 'Raz w miesiącu.'],
                ['q' => 'Jak się wypisać?', 'a' => 'Link do wypisu i zmiany preferencji znajdziesz w stopce każdej wiadomości.'],
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        Schema::table('newsletter_forms', function (Blueprint $table) {
            $table->dropColumn(['benefits', 'faq', 'proof']);
        });
    }
};
