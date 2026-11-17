<?php

namespace App\Services;

use App\Events\DonationPaid;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Darowizny: rejestracja płatności w Przelewy24 (ogólna albo na zbiórkę),
 * finalizacja po potwierdzeniu (webhook albo uzgadnianie), wpłaty wpisywane
 * ręcznie w panelu i zmiany statusu (zwrot).
 *
 * Kolejność ma znaczenie: do SZO wpłata trafia DOPIERO po transaction/verify,
 * bo rejestr darowizn w SZO nie zna statusów — każdy jego wiersz to wpłata,
 * która faktycznie wpłynęła.
 *
 * Idempotencja: status „paid” ustawia wyłącznie markPaid() pod blokadą wiersza
 * (SELECT … FOR UPDATE); zdarzenie DonationPaid (podziękowanie, przeliczenie
 * zbiórki, SZO) idzie tylko z wywołania, które faktycznie zmieniło status.
 * Dodatkowo p24_order_id jest unikalny w bazie, a kwota zbiórki liczona od
 * zera z sumy wpłat — powtórzony webhook niczego nie podwoi.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DonationService
{
    /** Kody wyjątków — kontroler zamienia je na teksty z panelu (FundraisingTexts). */
    public const ERROR_UNAVAILABLE = 1;
    public const ERROR_GATEWAY = 2;
    public const ERROR_PROCESSING = 3;

    private ?array $columns = null;

    public function __construct(
        private readonly Przelewy24Client $przelewy24,
        private readonly SzoClient $szo,
    ) {}

    /**
     * Zapisuje darowiznę i rejestruje transakcję w P24. Zwraca adres płatności.
     *
     * @throws RuntimeException gdy P24 nie jest skonfigurowane albo odrzuciło rejestrację
     */
    public function initiate(array $data, ?string $ip, ?Campaign $campaign = null): string
    {
        if (! $this->przelewy24->configured()) {
            throw new RuntimeException('Płatności online są chwilowo niedostępne.', self::ERROR_UNAVAILABLE);
        }

        $donation = Donation::create($this->existingColumns([
            'campaign_id' => $campaign?->id,
            'amount_grosze' => $data['amount_grosze'],
            'currency' => 'PLN',
            'type' => ($data['type'] ?? 'one_time') === 'recurring' && $campaign?->allow_recurring ? 'recurring' : 'one_time',
            'source' => 'online',
            'status' => 'pending',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'nip' => $data['nip'] ?? null,
            'dedication' => $data['dedication'] ?? null,
            'dedication_public' => (bool) ($data['dedication_public'] ?? false),
            'is_anonymous' => (bool) $data['is_anonymous'],
            'consent_newsletter' => (bool) ($data['consent_newsletter'] ?? false),
            'consent_at' => now(),
            'ip_address' => $ip,
        ]));

        return $this->register($donation);
    }

    /**
     * Ponowienie nieudanej płatności: nowa wpłata z tymi samymi danymi (nowy
     * sessionId — P24 nie pozwala zarejestrować tej samej sesji drugi raz).
     *
     * Zwraca null, gdy okazało się, że pierwotna wpłata jednak przeszła — wtedy
     * nie wolno pobierać pieniędzy drugi raz (kontroler pokazuje podziękowanie).
     *
     * @throws RuntimeException
     */
    public function retry(Donation $failed, ?string $ip): ?string
    {
        if (! $this->przelewy24->configured()) {
            throw new RuntimeException('Płatności online są chwilowo niedostępne.', self::ERROR_UNAVAILABLE);
        }

        // „failed” bywa skutkiem chwilowego błędu transaction/verify, a pieniądze już
        // wpłynęły — najpierw pytamy P24 o stan pierwotnej transakcji.
        if ($failed->isPaid() || $this->reconcile($failed)) {
            return null;
        }
        $state = $failed->source === 'manual' ? null : $this->przelewy24->findBySessionId($failed->session_id);
        if (in_array($state['status'] ?? null, [1, 2], true)) {
            throw new RuntimeException('Płatność jest jeszcze przetwarzana.', self::ERROR_PROCESSING);
        }

        $copy = $failed->replicate(['session_id', 'p24_order_id', 'paid_at', 'thanked_at', 'refunded_at', 'payload',
            'szo_donation_id', 'szo_contact_id', 'szo_synced_at', 'szo_error']);
        $copy->forceFill(['status' => 'pending', 'ip_address' => $ip, 'consent_at' => now()])->save();

        // Poprzednia, niedokończona próba nie powinna wisieć jako „oczekująca”.
        if ($failed->status === 'pending') {
            $failed->update(['status' => 'failed']);
        }

        return $this->register($copy);
    }

    /**
     * Potwierdza transakcję w P24 i oznacza darowiznę jako opłaconą.
     * Idempotentne — drugi webhook dla tej samej wpłaty niczego nie zmienia.
     */
    public function confirm(Donation $donation, int $p24OrderId, array $payload): bool
    {
        if ($donation->refresh()->isPaid()) {
            return true;
        }

        if (! $this->przelewy24->verifyTransaction($donation->session_id, $donation->amount_grosze, $donation->currency, $p24OrderId)) {
            // Warunkowo: równoległy webhook mógł ją właśnie zaksięgować.
            Donation::whereKey($donation->id)->where('status', '!=', 'paid')
                ->update(['status' => 'failed', 'payload' => json_encode($payload)]);
            $donation->refresh();

            return $donation->isPaid();
        }

        if ($this->markPaid($donation, ['p24_order_id' => $p24OrderId, 'payload' => $payload])) {
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

    /**
     * Wpłata wpisana ręcznie w panelu (np. przelew tradycyjny) — od razu opłacona.
     */
    public function recordManual(array $data, ?User $author, bool $sendThankYou): Donation
    {
        $donation = Donation::create([
            'campaign_id' => $data['campaign_id'] ?? null,
            'amount_grosze' => $data['amount_grosze'],
            'currency' => 'PLN',
            'type' => $data['type'] ?? 'one_time',
            'source' => 'manual',
            'status' => 'pending',
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'nip' => $data['nip'] ?? null,
            'dedication' => $data['dedication'] ?? null,
            'dedication_public' => (bool) ($data['dedication_public'] ?? false),
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? true),
            'admin_note' => $data['admin_note'] ?? null,
            'created_by' => $author?->id,
        ]);

        $this->markPaid($donation, ['paid_at' => $data['paid_at'] ?? now()], $sendThankYou);

        return $donation->refresh();
    }

    /**
     * Zmiana statusu z panelu. „paid” przechodzi przez markPaid() (zdarzenie raz),
     * „refunded” zapisuje datę zwrotu; każda zmiana przelicza kwotę zbiórki.
     */
    public function changeStatus(Donation $donation, string $status, bool $sendThankYou = false): void
    {
        if ($status === $donation->status) {
            return;
        }

        if ($status === 'paid') {
            if ($sendThankYou && $donation->thanked_at) {
                $donation->forceFill(['thanked_at' => null])->save();
            }
            $this->markPaid($donation, [], $sendThankYou);

            return;
        }

        $donation->update([
            'status' => $status,
            'refunded_at' => $status === 'refunded' ? now() : $donation->refunded_at,
        ]);
        $donation->campaign?->refreshCollected();
    }

    /**
     * Jedyne miejsce, które ustawia status „paid”. Zwraca true tylko wywołaniu,
     * które faktycznie zmieniło status — i tylko ono emituje DonationPaid.
     */
    public function markPaid(Donation $donation, array $attributes = [], bool $notify = true): bool
    {
        $marked = DB::transaction(function () use ($donation, $attributes) {
            $fresh = Donation::whereKey($donation->id)->lockForUpdate()->first();
            if (! $fresh || $fresh->isPaid()) {
                return false;
            }

            $fresh->update($this->existingColumns($attributes + [
                'status' => 'paid',
                'paid_at' => $attributes['paid_at'] ?? $fresh->paid_at ?? now(),
                'refunded_at' => null,
            ]));

            return true;
        });

        $donation->refresh();

        if ($marked) {
            DonationPaid::dispatch($donation, $notify);
        }

        return $marked;
    }

    /**
     * Push trafia na produkcję przed migracją (wdrożenie ręczne) — darowizna ogólna
     * nie może wtedy paść na brakującej kolumnie modułu zbiórek.
     */
    private function existingColumns(array $attributes): array
    {
        $this->columns ??= array_flip(Schema::getColumnListing('donations'));

        return array_intersect_key($attributes, $this->columns);
    }

    /** Rejestruje istniejącą wpłatę w P24 i zwraca adres płatności. */
    private function register(Donation $donation): string
    {
        $campaign = $donation->campaign;
        $texts = \App\Support\FundraisingTexts::for($campaign);
        $description = trim($texts->get('payment_description', $texts->vars($donation)))
            ?: (string) config('szo.donation_purpose', 'Darowizna');

        $token = $this->przelewy24->registerTransaction(
            $donation->session_id,
            $donation->amount_grosze,
            $donation->currency,
            $donation->email,
            $description,
            $campaign ? route('kampanie.thanks', [$campaign->slug, $donation]) : route('donation.thanks', $donation),
            route('przelewy24.webhook'),
        );

        if (! $token) {
            $donation->update(['status' => 'failed']);

            throw new RuntimeException('Nie udało się połączyć z Przelewy24. Spróbuj ponownie za chwilę.', self::ERROR_GATEWAY);
        }

        $donation->update(['payload' => ['register_token' => $token]]);

        return $this->przelewy24->paymentUrl($token);
    }
}
