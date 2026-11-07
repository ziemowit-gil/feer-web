<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strony publiczne nie powtarzają atrybutu id (kotwice, aria-labelledby i czytniki ekranu wymagają unikalnych). */
class DuplicateIdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
    }

    /** @return list<string> powtórzone id na stronie */
    private function duplicates(string $html): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $seen = [];
        foreach ((new \DOMXPath($dom))->query('//*[@id]') as $node) {
            $seen[$node->getAttribute('id')] = ($seen[$node->getAttribute('id')] ?? 0) + 1;
        }

        return array_keys(array_filter($seen, fn ($n) => $n > 1));
    }

    public function test_strona_kontaktu_nie_powtarza_id_w_zadnym_ukladzie(): void
    {
        foreach (['classic', 'card', 'split', 'tabs', 'feer'] as $layout) {
            SiteSetting::current()->update([
                'contact_layout' => $layout, 'site_template' => $layout === 'feer' ? 'feer' : 'default',
                'contact_phone' => '12 345 67 89', 'contact_online_meeting_url' => 'https://example.org/spotkanie',
                'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']],
                'contact_paczkomat_code' => 'KRA01M', 'contact_shipping_visible' => true, 'krs_number' => '0000779281',
            ]);
            \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

            $this->assertSame([], $this->duplicates($this->get('/kontakt')->assertOk()->getContent()), "kontakt, układ {$layout}");
        }
    }

    public function test_podstrony_kontaktu_feer_nie_powtarzaja_id(): void
    {
        SiteSetting::current()->update([
            'contact_layout' => 'feer', 'site_template' => 'feer', 'contact_schedule_enabled' => true, 'contact_online_meeting_url' => 'https://example.org/spotkanie',
            'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']], 'krs_number' => '0000779281',
        ]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        foreach (['spotkania', 'rachunki', 'dane-rejestrowe'] as $slug) {
            $res = $this->get('/kontakt/'.$slug);
            $this->assertSame(200, $res->getStatusCode(), $slug);
            $this->assertSame([], $this->duplicates($res->getContent()), $slug);
        }
    }

    public function test_strony_glowne_szablonow_nie_powtarzaja_id(): void
    {
        foreach (['default', 'feer', 'federation', 'municipality'] as $template) {
            SiteSetting::current()->update(['site_template' => $template]);
            \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

            foreach (['/', '/aktualnosci', '/faq'] as $url) {
                $res = $this->get($url);
                if ($res->getStatusCode() === 200) {
                    $this->assertSame([], $this->duplicates($res->getContent()), "{$template} {$url}");
                }
            }
        }
    }
}
