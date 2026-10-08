<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Publikuje podstronę „Odpłatna działalność pożytku publicznego" (założoną jako szkic migracją 2026_10_08_260000).
 * Idempotentna: dotyka tylko strony o tym adresie, która jest jeszcze szkicem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Page::withoutGlobalScopes()
            ->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')
            ->where('is_published', false)
            ->update(['is_published' => true]);
    }

    public function down(): void
    {
        Page::withoutGlobalScopes()
            ->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')
            ->update(['is_published' => false]);
    }
};
