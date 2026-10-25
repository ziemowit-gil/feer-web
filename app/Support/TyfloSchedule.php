<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Harmonogram konsultacji tyflo (typ strony `tyflo_consultations`): terminy z
 * `type_data.slots` oczyszczone, posortowane i podzielone na nadchodzące
 * oraz zakończone. Czas liczony względem now() (strefa aplikacji).
 */
class TyfloSchedule
{
    /** Forma konsultacji. */
    public const MODES = [
        'stationary' => 'Stacjonarnie',
        'online' => 'Online',
        'phone' => 'Telefonicznie',
    ];

    /** Status terminu — zawsze pokazywany tekstem (nie samym kolorem). */
    public const STATUSES = [
        'open' => 'Wolne miejsca',
        'few' => 'Ostatnie miejsca',
        'full' => 'Brak miejsc',
        'cancelled' => 'Odwołane',
    ];

    /**
     * Teksty widoku wg rodzaju strony: 'consultations' (konsultacje) albo 'trainings' (szkolenia).
     *
     * @return array<string, string>
     */
    public static function labels(string $kind): array
    {
        return $kind === 'trainings' ? [
            'about' => 'O szkoleniu',
            'people' => 'Prowadzący',
            'person' => 'Prowadzący',
            'schedule' => 'Harmonogram szkoleń',
            'next' => 'Najbliższy termin szkolenia',
            'cta' => 'Zapisz się na szkolenie',
            'genitive' => 'szkoleń tyflo',
            'enrol' => 'na szkolenie',
            'empty' => 'Nowe terminy szkoleń zostaną wkrótce opublikowane.',
            'a11y' => 'Dostępność szkolenia',
        ] : [
            'about' => 'O konsultacjach',
            'people' => 'Konsultanci',
            'person' => 'Konsultant',
            'schedule' => 'Harmonogram konsultacji',
            'next' => 'Najbliższy wolny termin',
            'cta' => 'Zapisz się na konsultacje',
            'genitive' => 'konsultacji tyflo',
            'enrol' => 'na konsultacje',
            'empty' => 'Nowe terminy konsultacji zostaną wkrótce opublikowane.',
            'a11y' => 'Dostępność konsultacji',
        ];
    }

    /** Statusy, przy których można się jeszcze zapisać. */
    public const BOOKABLE = ['open', 'few'];

    /**
     * Terminy ze znaną datą, posortowane rosnąco. Każdy: date (Carbon), date_to (Carbon|null, szkolenia wielodniowe), time_label, mode/mode_label, place, consultant, seats, status,
     * status_label, note, bookable, past.
     *
     * @param  array<int, mixed>  $rows  type_data['slots']
     * @return Collection<int, array<string, mixed>>
     */
    public static function slots(array $rows, ?Carbon $now = null): Collection
    {
        $now ??= now();

        return collect($rows)
            ->filter(fn ($r) => is_array($r) && filled($r['date'] ?? null))
            ->map(fn (array $r) => self::normalize($r, $now))
            ->filter()
            ->sortBy(fn (array $s) => $s['date']->format('Y-m-d').' '.($s['time_from'] ?: '00:00'))
            ->values();
    }

    /** @return array<string, mixed>|null */
    private static function normalize(array $r, Carbon $now): ?array
    {
        try {
            $date = Carbon::parse($r['date'])->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        // Data zakończenia (szkolenia wielodniowe) — pomijana, gdy wcześniejsza niż początek.
        $dateTo = null;
        if (filled($r['date_to'] ?? null)) {
            try {
                $dateTo = Carbon::parse($r['date_to'])->startOfDay();
            } catch (\Throwable) {
            }
            if ($dateTo && ($dateTo->lessThanOrEqualTo($date))) {
                $dateTo = null;
            }
        }

        $from = self::time($r['time_from'] ?? null);
        $to = self::time($r['time_to'] ?? null);
        $mode = array_key_exists($r['mode'] ?? '', self::MODES) ? $r['mode'] : null;
        $status = array_key_exists($r['status'] ?? '', self::STATUSES) ? $r['status'] : 'open';

        // Termin minął po godzinie końca (a bez niej — po początku, a bez godzin — po dniu).
        $endsAt = ($dateTo ?? $date)->copy()->setTimeFromTimeString(($to ?? $from ?? '23:59').':00');
        if (! $dateTo && $endsAt->lessThan($date->copy()->setTimeFromTimeString(($from ?? '00:00').':00'))) {
            $endsAt = $endsAt->addDay();
        }

        return [
            'date' => $date,
            'date_to' => $dateTo,
            'time_from' => $from,
            'time_to' => $to,
            'time_label' => match (true) {
                $from && $to => $from.' – '.$to,
                (bool) $from => 'od '.$from,
                default => null,
            },
            'mode' => $mode,
            'mode_label' => $mode ? self::MODES[$mode] : null,
            'place' => trim((string) ($r['place'] ?? '')) ?: null,
            'consultant' => trim((string) ($r['consultant'] ?? '')) ?: null,
            'seats' => trim((string) ($r['seats'] ?? '')) ?: null,
            'status' => $status,
            'status_label' => self::STATUSES[$status],
            'note' => trim((string) ($r['note'] ?? '')) ?: null,
            'past' => $endsAt->lessThan($now),
            'bookable' => in_array($status, self::BOOKABLE, true) && ! $endsAt->lessThan($now),
        ];
    }

    private static function time(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d/', $value) ? substr($value, 0, 5) : null;
    }

    /** Data po polsku, np. „wtorek, 3 listopada 2026”. */
    public static function dateLabel(Carbon $date): string
    {
        return $date->copy()->locale('pl')->translatedFormat('l, j F Y');
    }
}
