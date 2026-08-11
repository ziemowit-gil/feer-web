<?php

namespace App\Http\Controllers;

use App\Models\PayuOrder;
use App\Services\PaymentProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayuWebhookController extends Controller
{
    public function __construct(private readonly PaymentProcessor $processor) {}

    public function handle(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            Log::warning('PayU webhook: invalid signature');
            return response('Forbidden', 403);
        }

        $data = $request->json()->all();
        $order = $data['order'] ?? null;

        if (! $order) {
            return response('', 200);
        }

        $payuOrderId = $order['orderId'] ?? null;
        $status = $order['status'] ?? null;

        if (! $payuOrderId || ! $status) {
            return response('', 200);
        }

        $record = PayuOrder::where('payu_order_id', $payuOrderId)->first();

        if (! $record) {
            Log::info("PayU webhook: unknown order [{$payuOrderId}]");
            return response('', 200);
        }

        $record->update(['status' => $status, 'payload' => $data]);

        if ($status === 'COMPLETED') {
            $this->processor->processCompleted($record);
        }

        return response('', 200);
    }

    private function verifySignature(Request $request): bool
    {
        $secondKey = config('services.payu.second_key');
        if (! $secondKey) {
            return true;
        }

        $rawBody = $request->getContent();
        $incomingHeader = $request->header('OpenPayu-Signature', '');

        preg_match('/signature=([a-f0-9]+)/i', $incomingHeader, $matches);
        $incomingHash = $matches[1] ?? null;

        if (! $incomingHash) {
            return false;
        }

        $expected = hash('sha256', $rawBody . $secondKey);

        return hash_equals($expected, strtolower($incomingHash));
    }
}
