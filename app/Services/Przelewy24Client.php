<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\SklepOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klient REST API v3 Przelewy24 (rejestracja i weryfikacja transakcji).
 * Bez SDK — projekt nie ma zależności do P24, więc integracja jest ręczna,
 * tym samym wzorcem co istniejąca integracja PayU (app/Services/PaymentProcessor.php).
 *
 * Dane dostępowe pochodzą z panelu (Ustawienia → Logowanie i integracje →
 * Przelewy24, patrz SiteSetting::przelewy24Config()), z fallbackiem do .env
 * (config/przelewy24.php) — tak samo jak logowanie Microsoft/Google. Domyślnie
 * celuje w środowisko sandbox; przełączenie na produkcję to zmiana jednej
 * flagi w panelu (lub PRZELEWY24_SANDBOX w .env, gdy pole w panelu jest puste).
 */
class Przelewy24Client
{
    private function config(): array
    {
        return SiteSetting::current()->przelewy24Config();
    }

    public function configured(): bool
    {
        return SiteSetting::current()->przelewy24Configured();
    }

    public function baseUrl(): string
    {
        return $this->config()['sandbox']
            ? 'https://sandbox.przelewy24.pl'
            : 'https://secure.przelewy24.pl';
    }

    public function paymentUrl(string $token): string
    {
        return "{$this->baseUrl()}/trnRequest/{$token}";
    }

    /**
     * Rejestruje transakcję w P24 i zwraca token potrzebny do zbudowania
     * adresu płatności. Zwraca null, gdy integracja nie jest skonfigurowana
     * albo P24 odrzuci żądanie — kontroler zamienia to na komunikat dla kupującego.
     */
    public function register(SklepOrder $order, string $urlReturn, string $urlStatus): ?string
    {
        if (! $this->configured()) {
            Log::warning('[Przelewy24] Integracja nieskonfigurowana — brak danych w panelu ani w PRZELEWY24_*.');

            return null;
        }

        $config = $this->config();
        $sessionId = $order->session_id;
        $merchantId = (int) $config['merchant_id'];
        $amount = $order->amount_grosze;
        $currency = $order->currency;

        try {
            $response = $this->request($config)->post("{$this->baseUrl()}/api/v1/transaction/register", [
                'merchantId' => $merchantId,
                'posId' => (int) $config['pos_id'],
                'sessionId' => $sessionId,
                'amount' => $amount,
                'currency' => $currency,
                'description' => "Materiał: {$order->material?->title}",
                'email' => $order->buyer_email,
                'country' => 'PL',
                'language' => 'pl',
                'urlReturn' => $urlReturn,
                'urlStatus' => $urlStatus,
                'sign' => $this->sign([
                    'sessionId' => $sessionId,
                    'merchantId' => $merchantId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'crc' => $config['crc'],
                ]),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}: ".$response->body());
            }

            $token = $response->json('data.token');

            if (! $token) {
                throw new \RuntimeException('Brak tokenu w odpowiedzi P24.');
            }

            return $token;
        } catch (Throwable $e) {
            Log::warning('[Przelewy24] Rejestracja transakcji nie powiodła się: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Sprawdza podpis przychodzącego webhooka (urlStatus) przed zaufaniem jego treści.
     */
    public function verifyWebhookSignature(array $payload): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $expected = $this->sign([
            'merchantId' => $payload['merchantId'] ?? null,
            'posId' => $payload['posId'] ?? null,
            'sessionId' => $payload['sessionId'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'originAmount' => $payload['originAmount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'orderId' => $payload['orderId'] ?? null,
            'methodId' => $payload['methodId'] ?? null,
            'statement' => $payload['statement'] ?? null,
            'crc' => $this->config()['crc'],
        ]);

        return hash_equals($expected, (string) ($payload['sign'] ?? ''));
    }

    /**
     * Potwierdza transakcję (transaction/verify) — wymagane przez P24, zanim
     * status zostanie uznany za ostateczny.
     */
    public function confirmTransaction(SklepOrder $order, int $p24OrderId): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $config = $this->config();
        $sessionId = $order->session_id;
        $merchantId = (int) $config['merchant_id'];
        $amount = $order->amount_grosze;
        $currency = $order->currency;

        try {
            $response = $this->request($config)->put("{$this->baseUrl()}/api/v1/transaction/verify", [
                'merchantId' => $merchantId,
                'posId' => (int) $config['pos_id'],
                'sessionId' => $sessionId,
                'amount' => $amount,
                'currency' => $currency,
                'orderId' => $p24OrderId,
                'sign' => $this->sign([
                    'sessionId' => $sessionId,
                    'orderId' => $p24OrderId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'crc' => $config['crc'],
                ]),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}: ".$response->body());
            }

            return $response->json('data.status') === 'success';
        } catch (Throwable $e) {
            Log::warning('[Przelewy24] Weryfikacja transakcji nie powiodła się: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Odpytuje P24 o stan transakcji po naszym sessionId — siatka bezpieczeństwa
     * na wypadek niedostarczonego webhooka (patrz SklepOrderService::reconcile()).
     * Zwraca null, gdy integracja nie jest skonfigurowana, transakcja nie istnieje
     * w P24, albo żądanie się nie powiodło. `status` w odpowiedzi to kod P24:
     * 0 = brak płatności, 1 = płatność zaliczkowa, 2 = płatność wykonana,
     * 3 = płatność zwrócona. Samo `status === 2` NIE potwierdza jeszcze
     * transakcji — to nadal wymaga confirmTransaction() (transaction/verify).
     */
    public function findBySessionId(string $sessionId): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $config = $this->config();

        try {
            $response = $this->request($config)->get("{$this->baseUrl()}/api/v1/transaction/by/sessionId/{$sessionId}");

            if ($response->status() === 404) {
                return null;
            }

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}: ".$response->body());
            }

            return $response->json('data');
        } catch (Throwable $e) {
            Log::warning("[Przelewy24] Odpytanie o transakcję [{$sessionId}] nie powiodło się: ".$e->getMessage());

            return null;
        }
    }

    private function sign(array $data): string
    {
        return hash('sha384', json_encode($data));
    }

    private function request(array $config)
    {
        return Http::timeout(10)
            ->withBasicAuth($config['pos_id'], $config['api_key'])
            ->acceptJson()
            ->asJson();
    }
}
