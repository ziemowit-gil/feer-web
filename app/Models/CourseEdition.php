<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Termin (edycja) szkolenia: daty, harmonogram, miejsce, prowadzący i limit miejsc.
 * Zajęte miejsca liczą zgłoszenia ze statusem „oczekuje” i „potwierdzone”; zakończone też, bo uczestnik miejsce zajął.
 */
class CourseEdition extends Model
{
    public const ENROLLMENT = [
        'open' => 'Rekrutacja otwarta',
        'closed' => 'Rekrutacja zamknięta',
        'finished' => 'Zakończone',
    ];

    /** Statusy zgłoszeń, które zajmują miejsce w limicie. */
    public const SEAT_STATUSES = ['pending', 'confirmed', 'completed'];

    protected $fillable = [
        'course_id', 'starts_on', 'ends_on', 'schedule', 'location', 'meeting_url', 'instructor', 'capacity', 'enrollment_status',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'capacity' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class, 'edition_id');
    }

    /** Liczba zajętych miejsc (zgłoszenia z listy rezerwowej i anulowane nie liczą się). */
    public function getTakenSeatsAttribute(): int
    {
        return $this->registrations()->whereIn('status', self::SEAT_STATUSES)->count();
    }

    /** Wolne miejsca; nigdy poniżej zera. */
    public function getFreeSeatsAttribute(): int
    {
        return max(0, $this->capacity - $this->taken_seats);
    }

    public function isFull(): bool
    {
        return $this->free_seats <= 0;
    }

    public function isUpcoming(): bool
    {
        $end = $this->ends_on ?? $this->starts_on;

        return $this->enrollment_status !== 'finished'
            && $end !== null
            && $end->greaterThanOrEqualTo(today());
    }

    /** Czy formularz zapisu jest dostępny: rekrutacja otwarta i termin jeszcze się nie skończył. */
    public function acceptsRegistrations(): bool
    {
        return $this->enrollment_status === 'open' && $this->isUpcoming();
    }

    public function enrollmentLabel(): string
    {
        return self::ENROLLMENT[$this->enrollment_status] ?? (string) $this->enrollment_status;
    }

    /** Termin jako tekst: „5 listopada 2026” albo „5 – 30 listopada 2026”. */
    public function datesLabel(): string
    {
        $start = $this->starts_on->locale('pl');
        if (! $this->ends_on || $this->ends_on->isSameDay($this->starts_on)) {
            return $start->isoFormat('D MMMM YYYY');
        }

        return $start->isoFormat('D MMM').' – '.$this->ends_on->locale('pl')->isoFormat('D MMMM YYYY');
    }

    /**
     * Status nowego zgłoszenia: przy braku wolnych miejsc lista rezerwowa, inaczej „oczekuje” na potwierdzenie koordynatora.
     * Wywoływać wewnątrz transakcji z blokadą terminu, żeby dwie osoby nie zajęły ostatniego miejsca naraz.
     */
    public function statusForNewRegistration(): string
    {
        return $this->isFull() ? 'waitlist' : 'pending';
    }
}
