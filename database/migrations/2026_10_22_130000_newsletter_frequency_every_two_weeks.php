<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Newsletter wychodzi raz na 2 tygodnie (nie raz w miesiącu): domyślne teksty formularza i tematu. Zmienia tylko niezmienione w panelu treści. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('newsletter_forms')->get(['id', 'lead', 'faq']) as $form) {
            $lead = str_replace('Raz w miesiącu:', 'Raz na 2 tygodnie:', (string) $form->lead);
            $faq = $form->faq ? json_decode((string) $form->faq, true) : null;
            if (is_array($faq)) {
                $faq = array_map(fn ($i) => (($i['q'] ?? '') === 'Jak często wysyłacie newsletter?' && ($i['a'] ?? '') === 'Raz w miesiącu.') ? ['q' => $i['q'], 'a' => 'Raz na 2 tygodnie.'] : $i, $faq);
            }
            DB::table('newsletter_forms')->where('id', $form->id)->update([
                'lead' => $form->lead === null ? null : $lead,
                'faq' => is_array($faq) ? json_encode($faq, JSON_UNESCAPED_UNICODE) : $form->faq,
            ]);
        }

        if (Schema::hasTable('newsletter_topics')) {
            DB::table('newsletter_topics')->where('description', 'Co nowego w fundacji — raz w miesiącu.')->update(['description' => 'Co nowego w fundacji — raz na 2 tygodnie.']);
            Cache::forget('newsletter.topics.v1');
        }
    }

    public function down(): void
    {
        // Zmiana treści — bez cofania.
    }
};
