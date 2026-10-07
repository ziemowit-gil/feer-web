<?php

namespace Database\Seeders;

use App\Models\QuickAction;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Podmienia kolory serwisu na paletę z Brand booka FEER 2024:
 *   #1E6DFF — niebieski (kolor główny FEER), #1D1D1A — grafit, #CBD5E7 — jasny niebieski,
 *   #EA8F00 — pomarańcz (działania realizowane z partnerami / NGO).
 *
 * Zakres: ustawienia witryny (kolory marki 1–4 i kolor NGO) oraz kolory szybkich akcji. Kolory pozycji menu NIE są
 * ustawiane — „Kolor pozycji” jest opcjonalny i wybiera go redaktor w panelu (Menu → pozycja). Seeder jest idempotentny.
 *
 * Uruchomienie (na serwerze: php85):  php artisan db:seed --class=BrandbookColorsSeeder
 */
class BrandbookColorsSeeder extends Seeder
{
    public const BLUE = '#1e6dff';
    public const INK = '#1d1d1a';
    public const LIGHT_BLUE = '#cbd5e7';
    public const ORANGE = '#ea8f00';

    public function run(): void
    {
        // 1) Ustawienia witryny. brand_skip_contrast: #1E6DFF ma na bieli 4,48:1, więc bez tej flagi kreator kontrastu
        //    przyciemniłby kolor główny; tekst na jasnym tle i tak używa ciemniejszego wariantu (brand-dark).
        SiteSetting::query()->get()->each(function (SiteSetting $site) {
            $site->forceFill([
                'brand_color' => self::BLUE,
                'brand_color_2' => self::ORANGE,
                'brand_color_3' => self::INK,
                'brand_color_4' => self::LIGHT_BLUE,
                'ngo_color' => self::ORANGE,
                'brand_skip_contrast' => true,
                'ngo_skip_contrast' => true,
            ])->save();
        });

        // 2) Szybkie akcje: kolejno niebieski → grafit → pomarańcz (kolor akcji jest akcentem; tekst dobiera kontrast).
        $cycle = [self::BLUE, self::INK, self::ORANGE];
        QuickAction::query()->orderBy('order')->orderBy('id')->get()->values()->each(function (QuickAction $action, int $i) use ($cycle) {
            $action->forceFill(['color' => $cycle[$i % count($cycle)]])->save();
        });
    }
}
