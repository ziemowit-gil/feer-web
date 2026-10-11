<?php

namespace Database\Seeders;

use App\Models\QuickAction;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * „Na skróty” na stronie głównej (moduł quick_actions) — zestaw startowy FEER.
 * Kolory z brandbooka (niebieski #1E6DFF, grafit #1D1D1A, pomarańcz #EA8F00), ikony Material Symbols (outline).
 * Idempotentny: dopasowuje po adresie w bieżącej witrynie, więc ponowne uruchomienie aktualizuje skróty
 * zamiast je dublować; skrótów dodanych ręcznie w panelu nie rusza.
 *
 *   php artisan db:seed --class=QuickActionSeeder
 */
class QuickActionSeeder extends Seeder
{
    public function run(): void
    {
        $siteId = SiteSetting::current()->id;

        $actions = [
            [
                'label' => 'Materiały edukacyjne',
                'description' => 'Poradniki, scenariusze i materiały do pobrania',
                'icon' => 'mi-menu_book',
                'url' => '/materialy',
                'color' => '#1E6DFF',
            ],
            [
                'label' => 'Podcasty',
                'description' => 'Słuchaj, kiedy masz czas — bez sztywnych terminów',
                'icon' => 'mi-podcasts',
                'url' => '/podcasty',
                'color' => '#1D1D1A',
            ],
            [
                'label' => 'Wsparcie technologiczne dla NGO',
                'description' => 'Strony, dostępność i narzędzia dla organizacji',
                'icon' => 'mi-devices',
                'url' => '/dzialania/wsparcie-ngo',
                'color' => '#1E6DFF',
            ],
            [
                'label' => 'Wesprzyj fundację',
                'description' => 'Wpłać darowiznę online lub przelewem',
                'icon' => 'mi-volunteer_activism',
                'url' => '/wsparcie/darowizna',
                'color' => '#EA8F00',
                'is_negative' => true,
            ],
        ];

        foreach ($actions as $i => $data) {
            QuickAction::updateOrCreate(
                ['site_id' => $siteId, 'url' => $data['url']],
                $data + ['order' => $i + 1, 'is_negative' => false, 'cols' => 1, 'strip' => false],
            );
        }
    }
}
