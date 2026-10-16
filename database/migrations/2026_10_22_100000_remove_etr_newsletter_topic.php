<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ETR (tekst łatwy do czytania) to wersja tekstu danej treści, a nie osobny temat
 * newslettera. Usuwa temat „etr”: subskrybenci, którzy go wybrali, dostają „news”
 * (wersje ETR towarzyszą aktualnościom), a formularze tracą go z tematów i korzyści.
 */
return new class extends Migration
{
    public function up(): void
    {
        $strip = function (?string $json, bool $mergeIntoNews): ?string {
            $items = json_decode((string) $json, true);
            if (! is_array($items) || ! in_array('etr', $items, true)) {
                return null;
            }
            $items = array_values(array_diff($items, ['etr']));
            if ($mergeIntoNews && ! in_array('news', $items, true)) {
                $items[] = 'news';
            }

            return json_encode($items);
        };

        if (Schema::hasTable('subscribers') && Schema::hasColumn('subscribers', 'topics')) {
            DB::table('subscribers')->where('topics', 'like', '%"etr"%')->orderBy('id')->chunkById(500, function ($rows) use ($strip) {
                foreach ($rows as $r) {
                    if (($new = $strip($r->topics, true)) !== null) {
                        DB::table('subscribers')->where('id', $r->id)->update(['topics' => $new]);
                    }
                }
            });
        }

        if (Schema::hasTable('newsletter_forms')) {
            foreach (DB::table('newsletter_forms')->get() as $f) {
                $update = [];
                foreach (['topics' => false, 'default_topics' => false] as $col => $merge) {
                    if (isset($f->{$col}) && ($new = $strip($f->{$col}, $merge)) !== null) {
                        $update[$col] = $new;
                    }
                }
                $benefits = json_decode((string) ($f->benefits ?? ''), true);
                if (is_array($benefits)) {
                    $kept = array_values(array_filter($benefits, fn ($b) => ! is_string($b) || ! preg_match('/\bETR\b|łatw\w* (odczyt|do czytania)/iu', $b)));
                    if (count($kept) !== count($benefits)) {
                        $update['benefits'] = json_encode($kept, JSON_UNESCAPED_UNICODE);
                    }
                }
                if ($update) {
                    DB::table('newsletter_forms')->where('id', $f->id)->update($update);
                }
            }
        }

        if (Schema::hasTable('newsletter_topics')) {
            DB::table('newsletter_topics')->where('key', 'etr')->delete();
        }
    }

    public function down(): void
    {
        // Celowo bez przywracania — ETR nie jest tematem newslettera.
    }
};
