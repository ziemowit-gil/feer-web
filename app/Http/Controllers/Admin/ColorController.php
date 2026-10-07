<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeerBand;
use App\Models\NavItem;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use App\Support\Color;
use Database\Seeders\BrandbookColorsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Panel admin: jedno miejsce do zarządzania kolorami serwisu — paleta marki (kolory 1–4 i kolor NGO), podgląd na żywo
 * z kontrastem WCAG, gotowe palety oraz narzędzia masowe (kolory szybkich akcji, kolory pozycji menu).
 *
 * Metody: edit(), update(), applyBrandbook(), clearNavColors().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ColorController extends Controller
{
    /** Gotowe palety: kolory marki 1–4 i kolor NGO. */
    public const PRESETS = [
        'brandbook' => [
            'label' => 'Brandbook FEER 2024',
            'colors' => ['brand_color' => '#1e6dff', 'brand_color_2' => '#ea8f00', 'brand_color_3' => '#1d1d1a', 'brand_color_4' => '#cbd5e7', 'ngo_color' => '#ea8f00'],
        ],
        'monochrome' => [
            'label' => 'Grafit i niebieski',
            'colors' => ['brand_color' => '#1e6dff', 'brand_color_2' => '#1d1d1a', 'brand_color_3' => '#1d1d1a', 'brand_color_4' => '#cbd5e7', 'ngo_color' => '#1d1d1a'],
        ],
    ];

    /** Pola kolorów z opisem ich zastosowania. */
    public const FIELDS = [
        'brand_color' => ['Kolor główny', 'Przyciski, linki, akcenty, aktywne pozycje. Firmowy niebieski FEER.'],
        'brand_color_2' => ['Kolor 2', 'Drugi akcent (np. działania z partnerami).'],
        'brand_color_3' => ['Kolor 3', 'Grafit — tekst na jasnych kaflach, czarne przyciski.'],
        'brand_color_4' => ['Kolor 4', 'Jasne tło pomocnicze (kafle, wyróżnienia).'],
        'ngo_color' => ['Kolor NGO', 'Oznaczenie treści dla organizacji pozarządowych.'],
    ];

    /** Formularz zarządzania kolorami. */
    public function edit()
    {
        $settings = SiteSetting::current();

        return view('admin.colors.edit', [
            'settings' => $settings,
            'fields' => self::FIELDS,
            'presets' => self::PRESETS,
            'values' => collect(self::FIELDS)->map(fn ($meta, $key) => old($key, $settings->{$key} ?? ''))->all(),
            'navColored' => Schema::hasColumn('nav_items', 'accent_color') ? NavItem::whereNotNull('accent_color')->count() : 0,
            'actionsCount' => QuickAction::forCurrentSite()->count(),
            'bandsEnabled' => $settings->isModuleEnabled('feer_bands'),
            'bandsCount' => $settings->isModuleEnabled('feer_bands') ? FeerBand::forCurrentSite()->count() : 0,
        ]);
    }

    /** Zapisuje paletę marki (bez automatycznego przyciemniania — kolory firmowe zostają dokładnie takie, jak wpisane). */
    public function update(Request $request)
    {
        $rules = ['brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']];
        foreach (['brand_color_2', 'brand_color_3', 'brand_color_4', 'ngo_color'] as $key) {
            $rules[$key] = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        $data = $request->validate($rules);

        $settings = SiteSetting::current();
        $settings->forceFill(array_map(fn ($v) => $v ? strtolower($v) : null, $data) + ['brand_skip_contrast' => true, 'ngo_skip_contrast' => true])->save();

        return redirect()->route('admin.kolory.edit')->with('status', 'Kolory zostały zapisane.');
    }

    /** Ustawia kolory brandbooka w szybkich akcjach (cyklicznie niebieski → grafit → pomarańcz) — jak BrandbookColorsSeeder. */
    public function applyBrandbook()
    {
        $cycle = [BrandbookColorsSeeder::BLUE, BrandbookColorsSeeder::INK, BrandbookColorsSeeder::ORANGE];
        QuickAction::forCurrentSite()->orderBy('order')->orderBy('id')->get()->values()->each(
            fn (QuickAction $a, int $i) => $a->forceFill(['color' => $cycle[$i % count($cycle)]])->save()
        );

        return redirect()->route('admin.kolory.edit')->with('status', 'Kolory szybkich akcji ustawiono wg brandbooka.');
    }

    /** Usuwa własne kolory ze wszystkich pozycji menu (wracają do domyślnego wyglądu). */
    public function clearNavColors()
    {
        if (Schema::hasColumn('nav_items', 'accent_color')) {
            NavItem::whereNotNull('accent_color')->update(['accent_color' => null]);
        }

        return redirect()->route('admin.kolory.edit')->with('status', 'Kolory pozycji menu zostały usunięte.');
    }
}
