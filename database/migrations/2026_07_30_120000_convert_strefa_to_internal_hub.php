<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Zmienia typ automatycznie zakładanej strony „Strefa współpracownika"
     * (/strefa) z „internal" na „internal_hub" — jeden, spójny panel
     * współpracownika (hero + odnośniki + komunikaty SZO). Dostęp (MS365)
     * pozostaje bez zmian. Dotyka wyłącznie systemowej strony /strefa, która
     * nadal jest tą pierwotnie założoną (typ „internal"), więc ręcznie zmienione
     * strony zostają nietknięte. Wykonanie idempotentne.
     */
    public function up(): void
    {
        DB::table('pages')
            ->where('slug', 'strefa')
            ->where('is_system', true)
            ->where('type', 'internal')
            ->update([
                'type' => 'internal_hub',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('pages')
            ->where('slug', 'strefa')
            ->where('is_system', true)
            ->where('type', 'internal_hub')
            ->update([
                'type' => 'internal',
                'updated_at' => now(),
            ]);
    }
};
