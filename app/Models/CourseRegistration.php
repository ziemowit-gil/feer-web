<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Zgłoszenie uczestnika na termin szkolenia, z kodem referencji do tytułu przelewu. */
class CourseRegistration extends Model
{
    public const STATUSES = [
        'pending' => 'Oczekuje',
        'confirmed' => 'Potwierdzone',
        'waitlist' => 'Lista rezerwowa',
        'cancelled' => 'Anulowane',
        'completed' => 'Ukończone',
    ];

    protected $fillable = [
        'edition_id', 'first_name', 'last_name', 'email', 'phone', 'accessibility_needs', 'status', 'payment_reference', 'source',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            $r->payment_reference ??= self::newReference();
        });
    }

    /** Unikalny, łatwy do przepisania kod (bez 0/O/1/I): SZK-XXXXXX. */
    public static function newReference(): string
    {
        do {
            $code = 'SZK-'.collect(str_split('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'))->random(6)->implode('');
        } while (self::where('payment_reference', $code)->exists());

        return $code;
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(CourseEdition::class, 'edition_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Czy zgłoszenie zajmuje miejsce w limicie terminu. */
    public function holdsSeat(): bool
    {
        return in_array($this->status, CourseEdition::SEAT_STATUSES, true);
    }
}
