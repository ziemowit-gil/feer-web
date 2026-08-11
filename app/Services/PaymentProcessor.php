<?php

namespace App\Services;

use App\Models\PayuOrder;
use App\Models\Podcast;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\Plan;

class PaymentProcessor
{
    public function processCompleted(PayuOrder $order): void
    {
        $user = $order->user;

        if ($order->podcast_id) {
            $this->processPodcast($user, $order->podcast_id);
        } elseif ($order->plan_slug) {
            $this->processPlan($user, $order->plan_slug);
        }
    }

    private function processPlan(User $user, string $planSlug): void
    {
        $plan = Plan::where('slug', $planSlug)->first();
        if (! $plan) {
            Log::warning("PaymentProcessor: plan not found [{$planSlug}]");
            return;
        }

        $user->clearSubscriptionCache();

        if ($user->subscribed()) {
            $user->subscription()->renew();
        } else {
            $user->subscribe($plan);
        }
    }

    private function processPodcast(User $user, int $podcastId): void
    {
        $podcast = Podcast::find($podcastId);
        if (! $podcast) {
            Log::warning("PaymentProcessor: podcast not found [{$podcastId}]");
            return;
        }

        $slug = "podcast:{$podcastId}";

        Feature::firstOrCreate(
            ['slug' => $slug],
            ['name' => "Podcast: {$podcast->title}", 'type' => 'toggle', 'sort_order' => 100]
        );

        if (! $user->hasFeature($slug)) {
            $user->grantFeature($slug);
        }
    }
}
