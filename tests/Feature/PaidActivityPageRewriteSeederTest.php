<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PaidActivityPageRewriteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaidActivityPageRewriteSeederTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $content): Page
    {
        return Page::withoutGlobalScopes()->updateOrCreate(['slug' => 'odplatna-dzialalnosc-pozytku-publicznego'], [
            'title' => 'Odpłatna działalność pożytku publicznego: jak to u nas działa', 'type' => 'standard', 'is_published' => true, 'content' => $content,
        ]);
    }

    public function test_przepisuje_strone_z_definicja_beneficjenta_i_wyjasnieniem_dlaczego(): void
    {
        $page = $this->page('<h2>Jak płacić za płatne działania?</h2><p>Stary tekst.</p>');

        $this->seed(PaidActivityPageRewriteSeeder::class);
        $this->seed(PaidActivityPageRewriteSeeder::class);

        $html = $page->fresh()->content;
        $this->assertStringContainsString('<strong>Beneficjent</strong>, czyli osoba lub organizacja, która korzysta z działania, pokrywa część jego kosztów', $html);
        foreach (['Co znaczy „odpłatne”?', 'Dlaczego tak jest?', 'Na co idą wpłaty?', 'Czym jest odpłatna działalność pożytku publicznego?', 'Jak płacić za płatne działania?', 'Masz pytania?'] as $h2) {
            $this->assertStringContainsString('<h2>'.$h2.'</h2>', $html);
        }
        $this->assertSame(4, substr_count($html, '<li><strong>'), 'cztery zasady płatności zostają');
        $this->assertStringContainsString('BLIK nie jest dostępny przy rozliczeniach z dofinansowaniem PFRON', $html);
        $this->assertStringNotContainsString('—', strip_tags($html), 'bez myślników jako spójników');
    }

    public function test_nie_nadpisuje_strony_zmienionej_w_panelu(): void
    {
        $page = $this->page('<p>Własny tekst redakcji.</p>');

        $this->seed(PaidActivityPageRewriteSeeder::class);

        $this->assertSame('<p>Własny tekst redakcji.</p>', $page->fresh()->content);
    }
}
