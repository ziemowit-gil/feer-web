<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Darowizna jednorazowa wpłacana przez stronę (Przelewy24), przekazywana
 * do rejestru darowizn w SZO po potwierdzeniu płatności.
 *
 * Route key = session_id (UUID), żeby adres strony z podziękowaniem nie
 * zdradzał kolejnych numerów wpłat.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class Donation extends Model
{
    use BelongsToSite;

    public const STATUSES = [
        'pending' => 'Oczekująca',
        'paid' => 'Opłacona',
        'failed' => 'Nieudana',
        'refunded' => 'Zwrócona',
    ];

    protected $fillable = [
        'site_id', 'session_id', 'amount_grosze', 'currency', 'status',
        'first_name', 'last_name', 'email', 'phone', 'is_anonymous',
        'consent_newsletter', 'consent_at', 'ip_address',
        'p24_order_id', 'paid_at', 'payload',
        'szo_donation_id', 'szo_contact_id', 'szo_synced_at', 'szo_error',
    ];

    protected function casts(): array
    {
        return [
            'amount_grosze' => 'integer',
            'is_anonymous' => 'boolean',
            'consent_newsletter' => 'boolean',
            'consent_at' => 'datetime',
            'p24_order_id' => 'integer',
            'paid_at' => 'datetime',
            'payload' => 'array',
            'szo_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Donation $donation) {
            $donation->session_id ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'session_id';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /** Podpis na publicznej liście wpłat: „Anna K." albo „Wpłata anonimowa". */
    public function publicLabel(): string
    {
        if ($this->is_anonymous) {
            return 'Wpłata anonimowa';
        }

        return trim($this->first_name . ' ' . mb_substr($this->last_name, 0, 1) . '.');
    }

    /** Kwota w złotych do wyświetlenia, np. „60 zł" albo „62,50 zł". */
    public function amountLabel(): string
    {
        $value = $this->amount_grosze / 100;
        $formatted = fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', ' ')
            : number_format($value, 2, ',', ' ');

        return $formatted . ' zł';
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /** Opłacone, a jeszcze nieprzyjęte przez SZO — do ponowienia wysyłki. */
    public function scopePendingSzo(Builder $query): Builder
    {
        return $query->where('status', 'paid')->whereNull('szo_synced_at');
    }
}
