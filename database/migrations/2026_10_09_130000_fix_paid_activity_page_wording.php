<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Poprawia sformułowanie na stronie „Odpłatna działalność pożytku publicznego": niektóre działania są bezpłatne,
 * a niektóre płatne (nie każde ma dwie formy). Zmienia tylko dokładnie ten fragment, jeśli nie był edytowany.
 */
return new class extends Migration
{
    private const OLD = <<<'HTML'
<p>To samo działanie może mieć dwie formy:</p>
<ul>
<li><strong>Forma bezpłatna.</strong> Prowadzimy ją w naszych programach i projektach. Nie płacisz nic.</li>
<li><strong>Forma płatna.</strong> To dla osób, które chcą innego terminu, większego zakresu albo pomocy poza naszymi programami.</li>
</ul>
<p>Na stronie każdego działania zobaczysz, które formy są dostępne i jak się różnią.</p>
HTML;

    private const NEW = <<<'HTML'
<p>Niektóre działania robimy <strong>bezpłatnie</strong>, a niektóre <strong>płatnie</strong>.</p>
<ul>
<li><strong>Działania bezpłatne.</strong> Prowadzimy je w naszych programach i projektach. Nie płacisz nic.</li>
<li><strong>Działania płatne.</strong> Bierzemy za nie opłatę, na przykład gdy ktoś chce innego terminu, większego zakresu albo pomocy poza naszymi programami.</li>
</ul>
<p>Zdarza się też, że to samo działanie możesz wybrać w obu formach. Na stronie każdego działania zobaczysz, jak to wygląda.</p>
HTML;

    public function up(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')->first();
        if ($page && str_contains((string) $page->content, trim(self::OLD))) {
            $page->content = str_replace(trim(self::OLD), trim(self::NEW), $page->content);
            $page->save();
        }
    }

    public function down(): void
    {
        // Bez cofania — to poprawka treści.
    }
};
