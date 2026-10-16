<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Edytowalne teksty landingu formularza zapisu (/newsletter/{slug}/dolacz) oraz
 * zmiana nazwy „ETR” na „Łatwy Odczyt” w tematach i korzyściach (nie używamy skrótu ETR).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('newsletter_forms', 'landing')) {
            Schema::table('newsletter_forms', function (Blueprint $table) {
                $table->json('landing')->nullable()->after('proof');
            });
        }

        if (Schema::hasTable('newsletter_topics')) {
            DB::table('newsletter_topics')->where('label', 'Treści ETR (Łatwy Odczyt)')->update(['label' => 'Łatwy Odczyt']);
            \Illuminate\Support\Facades\Cache::forget('newsletter.topics.v1');
        }

        foreach (DB::table('newsletter_forms')->whereNotNull('benefits')->get(['id', 'benefits']) as $row) {
            $benefits = json_decode((string) $row->benefits, true);
            if (! is_array($benefits)) {
                continue;
            }
            $fixed = array_map(fn ($b) => $b === 'Treści w wersji Łatwy Odczyt (ETR)' ? 'Teksty w wersji Łatwy Odczyt' : $b, $benefits);
            if ($fixed !== $benefits) {
                DB::table('newsletter_forms')->where('id', $row->id)->update(['benefits' => json_encode($fixed, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('newsletter_forms', 'landing')) {
            Schema::table('newsletter_forms', function (Blueprint $table) {
                $table->dropColumn('landing');
            });
        }
    }
};
