<?php

namespace Database\Seeders;

use App\Models\QuickAction;
use Illuminate\Database\Seeder;

class QuickActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            [
                'label' => 'Zamów audyt WCAG',
                'icon'  => 'bi-clipboard2-check',
                'url'   => '/kontakt',
                'order' => 1,
                'color' => '#c31432',
            ],
            [
                'label' => 'Materiały edukacyjne',
                'icon'  => 'bi-mortarboard',
                'url'   => '/materialy',
                'order' => 2,
                'color' => '#2563eb',
            ],
            [
                'label' => 'Nadchodzące szkolenia',
                'icon'  => 'bi-calendar-event',
                'url'   => '/szkolenia',
                'order' => 3,
                'color' => '#16a34a',
            ],
            [
                'label' => 'Wesprzyj nas',
                'icon'  => 'bi-heart',
                'url'   => '/wesprzyj',
                'order' => 4,
                'color' => '#dc2626',
            ],
        ];

        foreach ($actions as $data) {
            QuickAction::updateOrCreate(
                ['label' => $data['label']],
                $data,
            );
        }
    }
}
