<?php

namespace Tests\Unit;

use App\Support\TableOfContents;
use PHPUnit\Framework\TestCase;

class TableOfContentsTest extends TestCase
{
    public function test_injects_unique_ids_and_lists_h2_h3(): void
    {
        $html = '<h2>Wstęp</h2><p>a</p><h3>Szczegóły</h3><h2 class="x">Wstęp</h2><h2 id="wlasne">Podsumowanie</h2>';

        [$out, $items] = TableOfContents::inject($html);

        $this->assertStringContainsString('<h2 id="wstep">Wstęp</h2>', $out);
        $this->assertStringContainsString('<h3 id="szczegoly">Szczegóły</h3>', $out);
        $this->assertStringContainsString('<h2 class="x" id="wstep-2">Wstęp</h2>', $out);
        $this->assertStringContainsString('<h2 id="wlasne">Podsumowanie</h2>', $out);

        $this->assertSame(
            [['wstep', 'Wstęp', 2], ['szczegoly', 'Szczegóły', 3], ['wstep-2', 'Wstęp', 2], ['wlasne', 'Podsumowanie', 2]],
            array_map(fn ($i) => [$i['id'], $i['text'], $i['level']], $items),
        );
    }

    public function test_returns_no_items_below_threshold_but_keeps_ids(): void
    {
        [$out, $items] = TableOfContents::inject('<h2>Jeden</h2><h2>Dwa</h2>');

        $this->assertSame([], $items);
        $this->assertStringContainsString('id="jeden"', $out);
    }

    public function test_handles_empty_content(): void
    {
        $this->assertSame(['', []], TableOfContents::inject(null));
        $this->assertSame(['<p>tekst</p>', []], TableOfContents::inject('<p>tekst</p>'));
    }

    public function test_strips_inline_markup_from_labels(): void
    {
        [, $items] = TableOfContents::inject('<h2>A <strong>b</strong> &amp; c</h2><h2>D</h2><h2>E</h2>');

        $this->assertSame('A b & c', $items[0]['text']);
        $this->assertSame('a-b-c', $items[0]['id']);
    }
}
