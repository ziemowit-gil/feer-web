<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Darowizny jednorazowe: rejestracja płatności w Przelewy24, finalizacja po
 * potwierdzeniu (webhook albo uzgadnianie) i przekazanie do SZO.
 *
 * Kolejność ma znaczenie: do SZO wpłata trafia DOPIERO po transaction/verify,
 * bo rejestr darowizn w SZO nie zna statusów — każdy jego wiersz to wpłata,
 * która faktycznie wpłynęła.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DonationService
{
    public function __construct(
        private readonly Przelewy24Client $przelewy24,
        private readonly SzoClient $szo,
    ) {}

    /**
     * Zapisuje darowiznę i rejestruje transakcję w P24. Zwraca adres płatności.
     *
     * @throws RuntimeException gdy P24 nie jest skonfigurowane albo odrzuciło rejestrację
     */
    public function initiate(array $data, ?string $ip): string
    {
        if (! $this->przelewy24->configured()) {
            throw new RuntimeException('Płatności online są chwilowo niedostępne.');
        }

        $donation = Donation::create([
            'amount_grosze' => $data['amount_grosze'],
            'currency' => 'PLN',
            'status' => 'pending',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_anonymous' => (bool) $data['is_anonymous'],
            'consent_newsletter' => (bool) ($data['consent_newsletter'] ?? false),
            'consent_at' => now(),
            'ip_address' => $ip,
        ]);

        $token = $this->przelewy24->registerTransaction(
            $donation->session_id,
            $donation->amount_grosze,
            $donation->currency,
            $donation->email,
            config('szo.donation_purpose', 'Darowizna'),
            route('donation.thanks', $donation),
            route('przelewy24.webhook'),
        );

        if (! $token) {
            $donation->update(['status' => 'failed']);

            throw new RuntimeException('Nie udało się połączyć z Przelewy24. Spróbuj ponownie za chwilę.');
        }

        $donation->update(['payload' => ['register_token' => $token]]);

        return $this->przelewy24->paymentUrl($token);
    }

    /**
     * Potwierdza transakcję w P24 i oznacza darowiznę jako opłaconą.
     * Idempotentne — drugi webhook dla tej samej wpłaty niczego nie zmienia.
     */
    public function confirm(Donation $donation, int $p24OrderId, array $payload): bool
    {
        if ($donation->isPaid()) {
            return true;
        }

        if (! $this->przelewy24->verifyTransaction($donation->session_id, $donation->amount_grosze, $donation->currency, $p24OrderId)) {
            $donation->update(['status' => 'failed', 'payload' => $payload]);

            return false;
        }

        $marked = DB::transaction(function () use ($donation, $p24OrderId, $payload) {
            $fresh = Donation::whereKey($donation->id)->lockForUpdate()->first();
            if ($fresh->isPaid()) {
                return false;
            }
            $fresh->update([
                'status' => 'paid',
                'paid_at' => now(),
                'p24_order_id' => $p24OrderId,
                'payload' => $payload,
            ]);

            return true;
        });

        $donation->refresh();
        if ($marked) {
            // Niepowodzenie nie cofa wpłaty — ponowi je donations:sync.
            $this->szo->pushDonation($donation);
        }

        return true;
    }

    /**
     * Siatka bezpieczeństwa na niedostarczony webhook: pyta P24 o stan
     * transakcji i finalizuje ją, jeśli została opłacona.
     */
    public function reconcile(Donation $donation): bool
    {
        if ($donation->isPaid()) {
            return true;
        }

        $data = $this->przelewy24->findBySessionId($donation->session_id);
        if (! $data) {
            return false;
        }

        if (($data['status'] ?? null) === 3) {
            $donation->update(['status' => 'refunded', 'payload' => $data]);

            return false;
        }

        $orderId = $data['orderId'] ?? null;
        if (($data['status'] ?? null) !== 2 || ! $orderId) {
            return false;
        }

        return $this->confirm($donation, (int) $orderId, $data);
    }
}
