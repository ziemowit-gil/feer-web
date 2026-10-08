<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD = 'Numery kont podajemy w mailu z fakturą. Opłaty wpłacaj tylko na konto do opłat.</li>';

    private const NEW = 'Numery kont podajemy w mailu z fakturą. Opłaty wpłacaj tylko na konto do opłat. <strong>Osoby indywidualne</strong>, które uczestniczą w szkoleniach, mają swój <strong>indywidualny numer rachunku</strong>. <strong>Podmioty</strong> (na przykład organizacje i firmy) płacą na <strong>numer ogólny</strong>.</li>';

    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('show_print_button')->default(true); // przycisk „Drukuj" na stronach i projektach
        });

        $page = Page::withoutGlobalScopes()->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')->first();
        if ($page && str_contains((string) $page->content, self::OLD)) {
            $page->content = str_replace(self::OLD, self::NEW, $page->content);
            $page->save();
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('show_print_button'));
    }
};
