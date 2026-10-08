<?php

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Zakłada podstronę „Odpłatna działalność pożytku publicznego" (prostym językiem) jako SZKIC do przejrzenia.
 * Idempotentna: jeśli strona o tym adresie już istnieje, nic nie robi. Treść trzeba zweryfikować ze statutem i zarządem.
 */
return new class extends Migration
{
    private const SLUG = 'odplatna-dzialalnosc-pozytku-publicznego';

    public function up(): void
    {
        if (Page::withTrashed()->withoutGlobalScopes()->where('slug', self::SLUG)->exists()) {
            return;
        }

        $content = <<<'HTML'
<p><strong>Krótko:</strong> nasza fundacja nie jest firmą. Za niektóre działania bierzemy opłatę, ale tylko po to, żeby pokryć koszty i pomagać dalej.</p>

<h2>Co to jest odpłatna działalność pożytku publicznego?</h2>
<p>Prawo pozwala fundacjom pobierać opłaty za usługi, które pomagają ludziom i pasują do celów fundacji. Takie działanie nazywa się <strong>odpłatną działalnością pożytku publicznego</strong>. Zasady opisuje ustawa o działalności pożytku publicznego i o wolontariacie.</p>

<h2>Czym różni się od zwykłego biznesu?</h2>
<ul>
<li>Robimy to, żeby pomagać, a nie żeby zarabiać.</li>
<li>Usługa musi pasować do celów fundacji, które mamy zapisane w statucie.</li>
<li>Pieniądze z opłat nie trafiają do kieszeni członków ani zarządu.</li>
</ul>

<h2>Jak to u nas działa?</h2>
<p>To samo działanie może mieć dwie formy:</p>
<ul>
<li><strong>Forma bezpłatna.</strong> Prowadzimy ją w naszych programach i projektach. Nie płacisz nic.</li>
<li><strong>Forma płatna.</strong> To dla osób, które chcą innego terminu, większego zakresu albo pomocy poza naszymi programami.</li>
</ul>
<p>Na stronie każdego działania zobaczysz, które formy są dostępne i jak się różnią.</p>

<h2>Na co idą pieniądze z opłat?</h2>
<p>Opłata pokrywa koszty przygotowania i prowadzenia zajęć. Jeśli coś zostanie, wydajemy to tylko na cele fundacji. Nie dzielimy tych pieniędzy między członków ani zarząd. Dzięki temu możemy robić więcej bezpłatnych działań.</p>

<h2>Masz pytania?</h2>
<p>Napisz do nas, chętnie wszystko wyjaśnimy. Dane kontaktowe znajdziesz na stronie <a href="/kontakt">Kontakt</a>.</p>
HTML;

        $page = new Page([
            'site_id' => SiteSetting::current()->id,
            'title' => 'Odpłatna działalność pożytku publicznego — jak to u nas działa',
            'slug' => self::SLUG,
            'content' => $content,
            'meta_title' => 'Odpłatna działalność pożytku publicznego',
            'meta_description' => 'Wyjaśniamy prostym językiem, czym jest odpłatna działalność pożytku publicznego i jak działa w naszej fundacji.',
            'is_published' => false, // szkic do weryfikacji treści
            'show_in_menu' => false,
            'show_side_nav' => false,
            'order' => 0,
        ]);
        $page->type = 'standard';
        $page->save();
    }

    public function down(): void
    {
        Page::withoutGlobalScopes()->where('slug', self::SLUG)->where('is_published', false)->forceDelete();
    }
};
