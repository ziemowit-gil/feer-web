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

    /**
     * Siatka bezpieczeństwa dla zamówień, do których nie dotarł webhook P24
     * (przeglądarka kupującego zamknięta przed powrotem, chwilowa awaria sieci
     * itp.) — odpytuje P24 o stan transakcji po sessionId i finalizuje jak
     * webhook, gdy P24 faktycznie potwierdza wpłatę. Wywoływane przez
     * `sklep:verify-pending` (harmonogram co 10 minut, patrz routes/console.php).
     * Zwraca true, gdy zamówienie jest (już było lub właśnie stało się) opłacone.
     */
    public function reconcile(SklepOrder $order): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        $data = $this->przelewy24->findBySessionId($order->session_id);

        if (! $data) {
            return false;
        }

        // 3 = płatność zwrócona przed potwierdzeniem — nie ma czego finalizować.
        if (($data['status'] ?? null) === 3) {
            $order->update(['status' => 'refunded', 'payload' => $data]);

            return false;
        }

        $orderId = $data['orderId'] ?? null;

        // 2 = płatność wykonana, ale to jeszcze nie potwierdzenie — wymagane
        // transaction/verify (patrz Przelewy24Client::confirmTransaction()).
        if (($data['status'] ?? null) !== 2 || ! $orderId) {
            return false;
        }

        if (! $this->przelewy24->confirmTransaction($order, (int) $orderId)) {
            return false;
        }

        $this->fulfill($order, $data);

        return true;
    }
}
