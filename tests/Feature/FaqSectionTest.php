<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use App\Support\FaqSection;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sekcja FAQ na dowolnej podstronie: wstawka [faq] z pytaniami z modułu FAQ (App\Support\FaqSection). */
class FaqSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Faq::create(['question' => 'Jak zapisać się na szkolenie?', 'answer' => "Wypełnij formularz.\nOddzwonimy.", 'category' => 'Szkolenia', 'is_published' => true, 'order' => 1]);
        Faq::create(['question' => 'Czy szkolenia są płatne?', 'answer' => 'Nie.', 'category' => 'Szkolenia', 'is_published' => true, 'order' => 2]);
        Faq::create(['question' => 'Jak przekazać 1,5%?', 'answer' => 'W zeznaniu PIT.', 'category' => 'Wsparcie', 'is_published' => true, 'order' => 3]);
        Faq::create(['question' => 'Szkic pytania', 'answer' => 'x', 'category' => 'Szkolenia', 'is_published' => false, 'order' => 4]);
    }

    public function test_wszystkie_opublikowane_pytania_w_nowym_ukladzie(): void
    {
        $html = ShortcodeParser::render('<p>[faq]</p>');

        $this->assertStringContainsString('class="faq-sec not-prose"', $html);
        $this->assertStringContainsString('Najczęstsze pytania</h2>', $html);
        $this->assertStringContainsString('Jak zapisać się na szkolenie?', $html);
        $this->assertStringContainsString('Jak przekazać 1,5%?', $html);
        $this->assertStringNotContainsString('Szkic pytania', $html);
        $this->assertStringContainsString('<details class="faq-sec-item">', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringNotContainsString('<p>[faq]', $html);
    }

    public function test_kategoria_tytul_limit_i_poziom(): void
    {
        $html = ShortcodeParser::render('[faq: Szkolenia; tytul=Pytania o szkolenia; wstep=Krótko o zapisach; limit=1; poziom=3]');

        $this->assertStringContainsString('Pytania o szkolenia</h3>', $html);
        $this->assertStringContainsString('Krótko o zapisach', $html);
        $this->assertStringContainsString('Jak zapisać się na szkolenie?', $html);
        $this->assertStringNotContainsString('Czy szkolenia są płatne?', $html);
        $this->assertStringNotContainsString('Jak przekazać', $html);
    }

    public function test_pusta_kategoria_nic_nie_pokazuje(): void
    {
        $this->assertSame('', trim(ShortcodeParser::render('[faq: Nieistniejąca]')));
        $this->assertSame(['A', 'B'], FaqSection::parse(' A, B ; limit=3')['categories']);
        $this->assertSame(3, FaqSection::parse('A; limit=3')['limit']);
    }

    public function test_panel_faq_podpowiada_wstawke(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.faq.index'))->assertOk()
            ->assertSee('Sekcja FAQ na dowolnej podstronie')
            ->assertSee('[faq: Szkolenia]')
            ->assertSee('[faq: Wsparcie]');
    }

    public function test_blok_faq_z_modulu_zamienia_sie_na_wstawke(): void
    {
        $this->assertSame('[faq]', FaqSection::shortcode([]));
        $this->assertSame('[faq: ; tytul=Pytania; limit=3]', FaqSection::shortcode(['title' => 'Pytania', 'limit' => 3]));
        $code = FaqSection::shortcode(['categories' => ['Szkolenia'], 'title' => 'O szkoleniach; ]x', 'level' => 3]);
        $this->assertSame('[faq: Szkolenia; tytul=O szkoleniach   x; poziom=3]', $code);

        $opts = FaqSection::parse(substr($code, 5, -1));
        $this->assertSame(['Szkolenia'], $opts['categories']);
        $this->assertSame(3, $opts['level']);

        $html = \App\Support\BlockRenderer::render([
            ['type' => 'faqmodule', 'attrs' => ['categories' => ['Wsparcie'], 'title' => 'Wsparcie & pytania']],
        ]);
        $this->assertStringContainsString('[faq: Wsparcie; tytul=Wsparcie &amp; pytania]', $html);

        $section = ShortcodeParser::render($html);
        $this->assertStringContainsString('Wsparcie &amp; pytania</h2>', $section);
        $this->assertStringContainsString('Jak przekazać 1,5%?', $section);
        $this->assertStringNotContainsString('Jak zapisać się', $section);
    }
}
