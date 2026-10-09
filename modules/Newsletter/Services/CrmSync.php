<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Synchronizacja subskrybenta do systemów zewnętrznych:
 *  - SZO (feerSZO): POST {szo.url}/api/v1/forms.php jako zgłoszenie formularza
 *    (slug z ustawień newslettera lub z formularza zapisu) ze zgodą `newsletter`;
 *    `external_id` = "newsletter:{uuid}", więc ponowienie nie dubluje kontaktu.
 *  - CRM (dowolny): webhook JSON podpisany HMAC-SHA256 (nagłówek X-Signature),
 *    zdarzenia: subscribed, confirmed, preferences_changed, unsubscribed, anonymized.
 *
 * Nic nie rzuca — błędy lądują w kolumnach szo_error / crm_error i w logu;
 * polecenie `newsletter:sync-crm` dosyła nieudane.
 */
class CrmSync
{
    public function sync(Subscriber $subscriber, string $event = 'confirmed', ?string $szoFormSlug = null): void
    {
        if ($subscriber->anonymized_at && $event !== 'anonymized') {
            return;
        }

        $site = SiteSetting::current();

        if ($site->newsletter_szo_sync && in_array($event, ['confirmed', 'preferences_changed'], true)) {
            $this->pushToSzo($subscriber, $szoFormSlug ?: (string) $site->newsletter_szo_form);
        }

        if ($site->newsletter_crm_sync && filled($site->newsletter_crm_webhook_url)) {
            $this->pushToWebhook($subscriber, $event, (string) $site->newsletter_crm_webhook_url, (string) $site->newsletter_crm_webhook_secret);
        }
    }

    public function szoEnabled(): bool
    {
        return (bool) config('szo.enabled') && filled(config('szo.url')) && filled(config('szo.token'));
    }

    private function pushToSzo(Subscriber $s, string $formSlug): void
    {
        if (! $this->szoEnabled()) {
            $s->forceFill(['szo_error' => 'SZO nie jest skonfigurowane (Ustawienia → Integracje → SZO).'])->saveQuietly();

            return;
        }
        if ($formSlug === '') {
            $s->forceFill(['szo_error' => 'Brak slugu formularza SZO dla newslettera (Newsletter → Ustawienia → Integracje).'])->saveQuietly();

            return;
        }

        try {
            $res = Http::withToken((string) config('szo.token'))
                ->timeout((int) config('szo.timeout', 5))
                ->acceptJson()->asJson()
                ->post(rtrim((string) config('szo.url'), '/') . '/api/v1/forms.php', [
                    'form'     => $formSlug,
                    'data'     => array_filter([
                        'email'         => $s->email,
                        'imie_nazwisko' => $s->name,
                        'telefon'       => $s->phone,
                        'notatka'       => 'Newsletter: ' . implode(', ', $s->topicLabels()) . ' · kanały: ' . implode(', ', $s->channels ?? ['email']),
                    ]),
                    'consents' => ['rodo', 'newsletter'],
                    'meta'     => [
                        'external_id' => 'newsletter:' . $s->uuid,
                        'url'         => route('newsletter.show'),
                        'source'      => $s->source,
                    ],
                ]);

            if ($res->successful() && $res->json('ok')) {
                $s->forceFill(['szo_contact_id' => $res->json('contact_id'), 'szo_synced_at' => now(), 'szo_error' => null])->saveQuietly();
                $s->logEvent('szo_synced', ['contact_id' => $res->json('contact_id')]);

                return;
            }
            $error = "SZO HTTP {$res->status()}: " . (is_string($res->json('error')) ? $res->json('error') : $res->body());
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        Log::warning("[Newsletter/SZO] Subskrybent {$s->id} nieprzekazany: {$error}");
        $s->forceFill(['szo_error' => mb_substr($error, 0, 500)])->saveQuietly();
    }

    private function pushToWebhook(Subscriber $s, string $event, string $url, string $secret): void
    {
        $payload = [
            'event'      => $event,
            'occurred_at'=> now()->toIso8601String(),
            'subscriber' => [
                'id'         => $s->uuid,
                'email'      => $s->email,
                'name'       => $s->name,
                'phone'      => $s->phone,
                'status'     => $s->status,
                'topics'     => $s->topics,
                'channels'   => $s->channels,
                'tags'       => $s->tags,
                'source'     => $s->source,
                'locale'     => $s->locale,
                'subscribed_at'   => $s->created_at?->toIso8601String(),
                'confirmed_at'    => $s->confirmed_at?->toIso8601String(),
                'unsubscribed_at' => $s->unsubscribed_at?->toIso8601String(),
            ],
            'consents' => $s->consents()->whereNull('revoked_at')->get(['channel', 'clause_text', 'granted_at', 'confirmed_at', 'source'])->toArray(),
        ];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $res = Http::timeout(8)->retry(2, 500)
                ->withHeaders(array_filter([
                    'Content-Type'   => 'application/json',
                    'X-Signature'    => $secret !== '' ? 'sha256=' . hash_hmac('sha256', (string) $body, $secret) : null,
                    'X-Event'        => $event,
                    'User-Agent'     => 'FEER-Newsletter/1.0',
                ]))
                ->withBody((string) $body, 'application/json')
                ->post($url);

            if ($res->successful()) {
                $s->forceFill(['crm_synced_at' => now(), 'crm_error' => null])->saveQuietly();
                $s->logEvent('crm_synced', ['event' => $event, 'status' => $res->status()]);

                return;
            }
            $error = "CRM HTTP {$res->status()}: " . mb_substr($res->body(), 0, 300);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        Log::warning("[Newsletter/CRM] Subskrybent {$s->id} ({$event}) nieprzekazany: {$error}");
        $s->forceFill(['crm_error' => mb_substr($error, 0, 500)])->saveQuietly();
    }
}
