<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** meta_excerpt(): opis strony z treści HTML bez shortcode'ów, znaczników i zbędnych spacji. */
class MetaExcerptTest extends TestCase
{
    public function test_usuwa_shortcody_znane_i_ogolne(): void
    {
        $this->assertSame('Lista otwartych naborów.', meta_excerpt('<p>[nabory]</p><p>Lista otwartych naborów.</p>'));
        $this->assertSame('Napisz do nas.', meta_excerpt('[formularz:kontakt] Napisz do nas. [klauzule-rodo:pl]'));
        $this->assertSame('Tekst po bloku.', meta_excerpt('[blok:12][jakis-nowy:param x]Tekst po bloku.'));
    }

    public function test_rozdziela_bloki_spacja_i_zwija_biale_znaki(): void
    {
        $this->assertSame('Tytuł Akapit pierwszy drugi.', meta_excerpt("<h2>Tytuł</h2><p>Akapit\n\n  pierwszy</p><p>drugi.</p>"));
        $this->assertSame('A & B "cytat"', meta_excerpt('<p>A &amp; B &quot;cytat&quot;</p>'));
        $this->assertSame('Widoczne', meta_excerpt('<style>.x{}</style><script>alert(1)</script><p>Widoczne</p>'));
    }

    public function test_przycina_do_limitu_i_obsluguje_puste(): void
    {
        $this->assertSame('', meta_excerpt(null));
        $this->assertSame('', meta_excerpt('  [nabory]  '));
        $this->assertSame(str_repeat('a', 10).'...', meta_excerpt(str_repeat('a', 50), 10));
        $this->assertLessThanOrEqual(163, mb_strlen(meta_excerpt(str_repeat('słowo ', 100))));
    }
}
