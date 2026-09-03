<?php

namespace App\Http\Controllers;

use App\Models\SklepOrder;
use App\Services\Przelewy24Client;
use App\Services\SklepOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class Przelewy24WebhookController extends Controller
{
    public function __construct(
        private readonly Przelewy24Client $przelewy24,
        private readonly SklepOrderService $orders,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->json()->all();

        if (! $this->przelewy24->verifyWebhookSignature($payload)) {
            Log::warning('[Przelewy24] Webhook: nieprawidłowy podpis.');

            return response('', 200);
        }

        $sessionId = $payload['sessionId'] ?? null;
        $orderId = $payload['orderId'] ?? null;

        if (! $sessionId || ! $orderId) {
            return response('', 200);
        }

        $order = SklepOrder::where('session_id', $sessionId)->first();

        if (! $order) {
            Log::info("[Przelewy24] Webhook: nieznane zamówienie [{$sessionId}]");

            return response('', 200);
        }

        if ($this->przelewy24->confirmTransaction($order, (int) $orderId)) {
            $this->orders->fulfill($order, $payload);
        } else {
            $order->update(['status' => 'failed', 'payload' => $payload]);
        }

        return response('', 200);
    }
}
