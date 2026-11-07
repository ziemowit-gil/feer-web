<?php

use App\Http\Controllers\PhoneBookController;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Książka telefoniczna zawsze pod /nasze-numery-telefonow: główna strona książki (typ „Kontakt — książka” bez filtra
 * działu, a w drugiej kolejności dawna „Książka telefoniczna”) dostaje ten adres, o ile nie zajmuje go inna strona.
 * Zmiana idzie przez model, więc stary adres przekierowuje na nowy (RedirectsOldSlug). Cofnięcie nic nie zmienia —
 * adres można zmienić w edycji strony.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages') || Page::withoutGlobalScopes()->withTrashed()->where('slug', PhoneBookController::SLUG)->exists()) {
            return;
        }

        $books = Page::withoutGlobalScopes()->whereIn('type', ['contact_book', 'phone_book'])
            ->orderByRaw("case type when 'contact_book' then 0 else 1 end")->orderByDesc('is_published')->orderBy('id')->get();
        $primary = $books->first(fn (Page $p) => blank(($p->type_data ?? [])['group'] ?? null));

        $primary?->update(['slug' => PhoneBookController::SLUG]);
    }

    public function down(): void {}
};
