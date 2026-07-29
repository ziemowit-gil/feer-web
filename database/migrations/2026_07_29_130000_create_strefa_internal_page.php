<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Zakłada z automatu stronę wewnętrzną „Strefa współpracownika" pod adresem
     * /strefa (slug „strefa"). Typ „internal" + tryb dostępu „microsoft" sprawia,
     * że wejście na /strefa przekierowuje niezalogowanych do logowania MS365
     * (guard „member"), a zalogowanym pokazuje treść strefy. Wykonanie jest
     * idempotentne — jeśli strona już istnieje, migracja jej nie dubluje.
     */
    public function up(): void
    {
        if (DB::table('pages')->where('slug', 'strefa')->exists()) {
            return;
        }

        DB::table('pages')->insert([
            'title' => 'Strefa współpracownika',
            'slug' => 'strefa',
            'type' => 'internal',
            'access_mode' => 'microsoft',
            'content' => '<p>Witamy w strefie współpracownika. Poniżej znajdziesz wewnętrzne '
                .'komunikaty i materiały dostępne tylko dla zalogowanych osób.</p>',
            'is_published' => true,
            'is_system' => true,
            'show_in_menu' => false,
            'meta_title' => 'Strefa współpracownika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Usuwamy tylko wtedy, gdy to nadal ta automatycznie założona strona
        // systemowa — ręcznie zmienione strony zostawiamy nietknięte.
        DB::table('pages')
            ->where('slug', 'strefa')
            ->where('is_system', true)
            ->delete();
    }
};
