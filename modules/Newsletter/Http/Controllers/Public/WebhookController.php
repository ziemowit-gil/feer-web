<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Modules\Newsletter\Jobs\ProcessBounceEvent;

/**
 * Webhooki zwrotne dostawców: ses (SNS), mailgun, postmark, generic.
 * Każdy ładunek jest normalizowany do {type, provider, event_id, email, message_id, code, reason, at}.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class WebhookController extends Controller
{
    public function handle(Request $request, string $provider)
    {
        $site = SiteSetting::current();

        $events = match ($provider) {
            'ses'      => $this->ses($request),
            'mailgun'  => $this->mailgun($request, (string) $site->newsletter_mailgun_secret),
            'postmark' => $this->postmark($request, (string) $site->newsletter_webhook_secret),
            'generic'  => $this->generic($request, (string) $site->newsletter_webhook_secret),
            default    => abort(404),
        };

        if ($events === null) {
            abort(403, 'Nieprawidłowy podpis webhooka.');
        }

        foreach ($events as $event) {
            ProcessBounceEvent::dispatch($event);
        }

        return response()->json(['ok' => true, 'received' => count($events)]);
    }

    /** Amazon SES przez SNS (potwierdzenie subskrypcji + notyfikacje). */
    private function ses(Request $request): ?array
    {
        $body = json_decode((string) $request->getContent(), true) ?: [];

        if (($body['Type'] ?? '') === 'SubscriptionConfirmation' && ! empty($body['SubscribeURL'])) {
            \Illuminate\Support\Facades\Http::get($body['SubscribeURL']);

            return [];
        }

        $msg = json_decode((string) ($body['Message'] ?? ''), true) ?: $body;
        $type = strtolower((string) ($msg['notificationType'] ?? $msg['eventType'] ?? ''));
        $messageId = $msg['mail']['messageId'] ?? null;
        $out = [];

        if ($type === 'bounce') {
            $hard = strtolower((string) ($msg['bounce']['bounceType'] ?? '')) === 'permanent';
            foreach ($msg['bounce']['bouncedRecipients'] ?? [] as $r) {
                $out[] = ['type' => $hard ? 'hard' : 'soft', 'provider' => 'ses', 'event_id' => ($msg['bounce']['feedbackId'] ?? $messageId) . ':' . ($r['emailAddress'] ?? ''),
                    'email' => $r['emailAddress'] ?? null, 'message_id' => $messageId, 'code' => $r['status'] ?? null, 'reason' => $r['diagnosticCode'] ?? null, 'at' => $msg['bounce']['timestamp'] ?? now(), 'raw' => $msg['bounce'] ?? null];
            }
        } elseif ($type === 'complaint') {
            foreach ($msg['complaint']['complainedRecipients'] ?? [] as $r) {
                $out[] = ['type' => 'complaint', 'provider' => 'ses', 'event_id' => ($msg['complaint']['feedbackId'] ?? $messageId) . ':' . ($r['emailAddress'] ?? ''),
                    'email' => $r['emailAddress'] ?? null, 'message_id' => $messageId, 'reason' => $msg['complaint']['complaintFeedbackType'] ?? null, 'at' => $msg['complaint']['timestamp'] ?? now()];
            }
        } elseif ($type === 'delivery') {
            $out[] = ['type' => 'delivered', 'provider' => 'ses', 'message_id' => $messageId, 'at' => $msg['delivery']['timestamp'] ?? now()];
        }

        return $out;
    }

    private function mailgun(Request $request, string $apiKey): ?array
    {
        $sig = $request->input('signature', []);
        if ($apiKey !== '' && ! hash_equals(hash_hmac('sha256', ($sig['timestamp'] ?? '') . ($sig['token'] ?? ''), $apiKey), (string) ($sig['signature'] ?? ''))) {
            return null;
        }
        $e = $request->input('event-data', []);
        $event = strtolower((string) ($e['event'] ?? ''));
        $base = ['provider' => 'mailgun', 'event_id' => $e['id'] ?? null, 'email' => $e['recipient'] ?? null,
            'message_id' => isset($e['message']['headers']['message-id']) ? '<' . trim($e['message']['headers']['message-id'], '<>') . '>' : null,
            'reason' => $e['delivery-status']['description'] ?? ($e['delivery-status']['message'] ?? null), 'code' => isset($e['delivery-status']['code']) ? (string) $e['delivery-status']['code'] : null,
            'at' => isset($e['timestamp']) ? \Carbon\Carbon::createFromTimestamp((float) $e['timestamp']) : now(), 'raw' => $e];

        return match ($event) {
            'failed'    => [array_merge($base, ['type' => ($e['severity'] ?? '') === 'permanent' ? 'hard' : 'soft'])],
            'complained'=> [array_merge($base, ['type' => 'complaint'])],
            'delivered' => [array_merge($base, ['type' => 'delivered'])],
            default     => [],
        };
    }

    private function postmark(Request $request, string $secret): ?array
    {
        if ($secret !== '' && ! hash_equals($secret, (string) ($request->header('X-Webhook-Secret') ?? $request->query('secret')))) {
            return null;
        }
        $e = $request->all();
        $type = (string) ($e['RecordType'] ?? '');
        $base = ['provider' => 'postmark', 'event_id' => isset($e['ID']) ? (string) $e['ID'] : null, 'email' => $e['Email'] ?? ($e['Recipient'] ?? null),
            'message_id' => isset($e['MessageID']) ? $e['MessageID'] : null, 'reason' => $e['Description'] ?? ($e['Details'] ?? null), 'at' => $e['BouncedAt'] ?? ($e['DeliveredAt'] ?? now()), 'raw' => $e];

        return match ($type) {
            'Bounce'        => [array_merge($base, ['type' => in_array($e['Type'] ?? '', ['HardBounce', 'BadEmailAddress', 'Blocked', 'ManuallyDeactivated', 'Unsubscribe'], true) ? 'hard' : 'soft'])],
            'SpamComplaint' => [array_merge($base, ['type' => 'complaint'])],
            'Delivery'      => [array_merge($base, ['type' => 'delivered'])],
            default         => [],
        };
    }

    /** Generyczny: JSON {events:[{type:hard|soft|complaint|delivered, email, message_id, reason, id}]} podpisany HMAC w X-Signature. */
    private function generic(Request $request, string $secret): ?array
    {
        if ($secret !== '') {
            $expected = 'sha256=' . hash_hmac('sha256', (string) $request->getContent(), $secret);
            if (! hash_equals($expected, (string) $request->header('X-Signature'))) {
                return null;
            }
        }
        $out = [];
        foreach ((array) $request->input('events', []) as $e) {
            if (! in_array($e['type'] ?? '', ['hard', 'soft', 'complaint', 'delivered'], true)) {
                continue;
            }
            $out[] = ['type' => $e['type'], 'provider' => 'generic', 'event_id' => $e['id'] ?? null, 'email' => $e['email'] ?? null,
                'message_id' => $e['message_id'] ?? null, 'delivery_uuid' => $e['delivery_uuid'] ?? null, 'code' => $e['code'] ?? null, 'reason' => $e['reason'] ?? null, 'at' => $e['at'] ?? now()];
        }

        return $out;
    }
}
