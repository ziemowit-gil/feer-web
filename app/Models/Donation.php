<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Darowizna wpłacana przez stronę (Przelewy24) — ogólna na cele statutowe
 * albo na konkretną zbiórkę (campaign_id) — lub wpisana ręcznie w panelu
 * (source = manual, np. przelew tradycyjny). Wpłaty online po potwierdzeniu
 * trafiają do rejestru darowizn w SZO; ręczne nie (SZO ma własny import z banku).
 *
 * Status „paid” odpowiada „completed” z dokumentacji operatorów płatności.
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

    public const TYPES = [
        'one_time' => 'Jednorazowa',
        'recurring' => 'Cykliczna (co miesiąc)',
    ];

    public const SOURCES = [
        'online' => 'Online (Przelewy24)',
        'manual' => 'Wpisana ręcznie',
    ];

    protected $fillable = [
        'site_id', 'campaign_id', 'session_id', 'amount_grosze', 'currency', 'type', 'source', 'status',
        'first_name', 'last_name', 'email', 'phone', 'company_name', 'nip', 'is_anonymous',
        'dedication', 'dedication_public', 'admin_note', 'created_by',
        'consent_newsletter', 'consent_at', 'ip_address',
        'p24_order_id', 'paid_at', 'thanked_at', 'refunded_at', 'payload',
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
            'thanked_at' => 'datetime',
            'refunded_at' => 'datetime',
            'dedication_public' => 'boolean',
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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isManual(): bool
    {
        return $this->source === 'manual';
    }

    public function isRecurring(): bool
    {
        return $this->type === 'recurring';
    }

    /** Dedykacja do pokazania publicznie (tylko gdy darczyńca na to pozwolił). */
    public function publicDedication(): ?string
    {
        return $this->dedication_public && filled($this->dedication) ? $this->dedication : null;
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

        if (blank($this->first_name) && filled($this->company_name)) {
            return $this->company_name;
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

    /** Opłacone online, a jeszcze nieprzyjęte przez SZO — do ponowienia wysyłki. */
    public function scopePendingSzo(Builder $query): Builder
    {
        return $query->where('status', 'paid')->whereNull('szo_synced_at')
            ->where(fn ($q) => $q->whereNull('source')->orWhere('source', '!=', 'manual'));
    }
}
