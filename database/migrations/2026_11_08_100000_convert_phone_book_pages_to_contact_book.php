<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Contact\Support\LegacyPagesConverter;

/**
 * Moduł Kontakt: dawne strony „Książka telefoniczna” (typ phone_book, osoby w polach strony) stają się stronami
 * „Kontakt — książka” (typ contact_book, osoby w tabeli książki telefonicznej). Kopia przenoszonych stron zapisuje się
 * w storage/app/module-backups/contact-<data>.json; cofnięcie (down) przywraca z niej dawny typ.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(LegacyPagesConverter::class)->convert();
    }

    public function down(): void
    {
        app(LegacyPagesConverter::class)->restore();
    }
};
