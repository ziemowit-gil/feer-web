<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Dopisuje na stronie „Odpłatna działalność pożytku publicznego", dlaczego niektóre działania są płatne:
 * nie mamy na nie środków albo są ograniczone, więc uczestnik dokłada się do kosztów. Zmienia tylko nieedytowany fragment.
 */
return new class extends Migration
{
    private const OLD = '<li><strong>Działania płatne.</strong> Bierzemy za nie opłatę, na przykład gdy ktoś chce innego terminu, większego zakresu albo pomocy poza naszymi programami.</li>';

    private const NEW = '<li><strong>Działania płatne.</strong> To te, na które nie mamy środków albo mamy ich za mało. Mimo to chcemy je robić, więc ich koszty dzielimy: Ty dokładasz się do kosztów, a my pokrywamy resztę. Tak jest na przykład, gdy ktoś chce innego terminu, większego zakresu albo pomocy poza naszymi programami.</li>';

    public function up(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')->first();
        if ($page && str_contains((string) $page->content, self::OLD)) {
            $page->content = str_replace(self::OLD, self::NEW, $page->content);
            $page->save();
        }
    }

    public function down(): void
    {
        // Bez cofania — to poprawka treści.
    }
};
