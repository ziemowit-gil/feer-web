<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\Plan;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $accessFeature = Feature::firstOrCreate(
            ['slug' => 'access-premium-podcasts'],
            ['name' => 'Dostęp do podcastów premium', 'type' => 'toggle', 'sort_order' => 1]
        );

        $downloadsFeature = Feature::firstOrCreate(
            ['slug' => 'podcast-downloads'],
            ['name' => 'Pobieranie podcastów', 'type' => 'toggle', 'sort_order' => 2]
        );

        $free = Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Darmowy',
                'description' => 'Dostęp do wszystkich bezpłatnych podcastów.',
                'is_free' => true,
                'is_active' => true,
                'billing_period' => 1,
                'billing_interval' => 'month',
                'sort_order' => 0,
            ]
        );

        $plus = Plan::firstOrCreate(
            ['slug' => 'plus'],
            [
                'name' => 'Plus (miesięczny)',
                'description' => 'Pełny dostęp do podcastów premium — rozliczenie miesięczne.',
                'is_free' => false,
                'is_active' => true,
                'billing_period' => 1,
                'billing_interval' => 'month',
                'sort_order' => 1,
            ]
        );

        $premium = Plan::firstOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium (roczny)',
                'description' => 'Pełny dostęp do podcastów premium + pobieranie — rozliczenie roczne.',
                'is_free' => false,
                'is_active' => true,
                'billing_period' => 1,
                'billing_interval' => 'year',
                'sort_order' => 2,
            ]
        );

        foreach ([$plus, $premium] as $plan) {
            if (! $plan->features()->where('slug', 'access-premium-podcasts')->exists()) {
                $plan->features()->attach($accessFeature->id, ['value' => '0']);
            }
        }

        if (! $premium->features()->where('slug', 'podcast-downloads')->exists()) {
            $premium->features()->attach($downloadsFeature->id, ['value' => '0']);
        }
    }
}
