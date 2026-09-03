<?php

namespace App\Services;

use App\Mail\SklepOrderPaidMail;
use App\Models\EducationalMaterial;
use App\Models\SklepOrder;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Spina rejestrację zamówienia w Przelewy24 (Przelewy24Client) z lokalnym
 * rekordem SklepOrder oraz wysyłką dostępu po opłaceniu.
 */
class SklepOrderService
{
    public function __construct(private readonly Przelewy24Client $przelewy24) {}

    /**
     * Tworzy zamówienie i rejestruje transakcję w P24. Rzuca RuntimeException,
     * gdy P24 odrzuci rejestrację — kontroler zamienia to na komunikat dla kupującego.
     */
    public function initiate(EducationalMaterial $material, string $email, ?string $name, ?int $userId): SklepOrder
    {
        $order = SklepOrder::create([
            'educational_material_id' => $material->id,
            'buyer_name' => $name,
            'buyer_email' => $email,
            'user_id' => $userId,
            'status' => 'pending',
            'amount_grosze' => $material->price_grosze,
            'currency' => $material->currency,
        ]);

        $urlReturn = route('sklep.confirmation', $order);
        $urlStatus = route('przelewy24.webhook');

        $token = $this->przelewy24->register($order, $urlReturn, $urlStatus);

        if (! $token) {
            throw new RuntimeException('Nie udało się zarejestrować płatności w Przelewy24. Spróbuj ponownie za chwilę.');
        }

        $order->update(['payload' => ['register_token' => $token]]);

        return $order;
    }

    public function paymentUrl(SklepOrder $order): string
    {
        return $this->przelewy24->paymentUrl($order->payload['register_token'] ?? '');
    }

    /**
     * Finalizuje płatność po potwierdzeniu przez P24 (webhook + transaction/verify).
     * Idempotentne — P24 może wysłać webhook wielokrotnie.
     */
    public function fulfill(SklepOrder $order, array $webhookPayload): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->markPaid($webhookPayload);
        $order->update(['access_delivered_at' => now()]);

        Mail::to($order->buyer_email)->send(new SklepOrderPaidMail($order));
    }

    public function resend(SklepOrder $order): void
    {
        Mail::to($order->buyer_email)->send(new SklepOrderPaidMail($order));
    }
}
