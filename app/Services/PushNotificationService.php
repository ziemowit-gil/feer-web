<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Serwis wysyłający powiadomienia Web Push do wszystkich subskrybentów.
 * Automatycznie usuwa wygasłe subskrypcje (HTTP 404/410 z serwera push).
 */
class PushNotificationService
{
    /**
     * Wysyła powiadomienie push do wszystkich aktywnych subskrybentów.
     *
     * @return int Liczba kolejkowanych wysyłek (nie gwarantuje dostarczenia).
     */
    public function send(string $title, string $body, string $url = '/', ?string $icon = null): int
    {
        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $auth = [
            'VAPID' => [
                'subject'    => config('webpush.vapid.subject'),
                'publicKey'  => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'icon'  => $icon,
        ]);

        $sent = 0;

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->p256dh_key,
                'authToken'       => $sub->auth_token,
                'contentEncoding' => 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, $payload);
            $sent++;
        }

        // Usuwamy subskrypcje, które serwer push odrzucił jako nieaktywne.
        foreach ($webPush->flush() as $report) {
            if (! $report->isSuccess()
                && in_array($report->getResponse()?->getStatusCode(), [404, 410], true)
            ) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                $sent--;
            }
        }

        return max(0, $sent);
    }
}
