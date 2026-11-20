<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Harmonogram pojedynczego bloku edytora blokowego: „Pokaż od” (`from`) i „Ukryj od” (`until`).
 *
 * Bloki zamieniają się w HTML przy zapisie, więc blok z terminem trafia do `content` owinięty komentarzami
 * <!--be-okno:N od=… do=…-->…<!--/be-okno:N-->, a o tym, czy go pokazać, decyduje filter() przy wyświetlaniu
 * (ShortcodeParser::render). Redakcja nie musi pamiętać o sprzątaniu strony po terminie.
 *
 * Terminy to czas lokalny redakcji (Europe/Warsaw), zapisany jak w polu datetime-local: 2026-12-01T08:00.
 * Blok jest widoczny, gdy from ≤ teraz < until (dowolny z terminów może być pusty).
 */
class BlockSchedule
{
    public const TIMEZONE = 'Europe/Warsaw';

    private const FORMAT = 'Y-m-d\TH:i';

    /** Wartość pola datetime-local (także z sekundami albo spacją zamiast „T”) albo null, gdy pusta lub błędna. */
    public static function clean(mixed $value): ?string
    {
        if (! is_scalar($value) || ! preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(?::\d{2})?$/', trim((string) $value), $m)) {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $m[1].' '.$m[2], new \DateTimeZone(self::TIMEZONE));

        return $dt && $dt->format('Y-m-d H:i') === $m[1].' '.$m[2] ? $dt->format(self::FORMAT) : null;
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE);
    }

    /**
     * Stan okna: 'scheduled' (jeszcze się nie pokazał), 'expired' (już zniknął), 'active' (widoczny w oknie)
     * albo null (bez terminów).
     */
    public static function state(?string $from, ?string $until, ?CarbonImmutable $now = null): ?string
    {
        if ($from === null && $until === null) {
            return null;
        }
        $stamp = ($now ?? self::now())->setTimezone(self::TIMEZONE)->format(self::FORMAT);
        if ($from !== null && $stamp < $from) {
            return 'scheduled';
        }
        if ($until !== null && $stamp >= $until) {
            return 'expired';
        }

        return 'active';
    }

    /** Stan bloku z drzewa bloków (klucze `from` / `until`). */
    public static function stateOf(array $block, ?CarbonImmutable $now = null): ?string
    {
        return self::state(self::clean($block['from'] ?? null), self::clean($block['until'] ?? null), $now);
    }

    /** „od 1.12.2026 08:00 do 24.12.2026 00:00” — do opisów w panelu. */
    public static function describe(?string $from, ?string $until): string
    {
        $fmt = fn (string $v) => \DateTimeImmutable::createFromFormat(self::FORMAT, $v)->format('j.m.Y H:i');

        return trim(($from ? 'od '.$fmt($from) : '').' '.($until ? 'do '.$fmt($until) : ''));
    }

    /** Krótki opis stanu bloku do listy bloków w karcie strony albo null, gdy blok nie ma terminów. */
    public static function label(array $block, ?CarbonImmutable $now = null): ?string
    {
        $from = self::clean($block['from'] ?? null);
        $until = self::clean($block['until'] ?? null);

        return match (self::state($from, $until, $now)) {
            'scheduled' => 'pokaże się '.self::describe($from, null),
            'expired' => 'zniknął '.preg_replace('/^do /', '', self::describe(null, $until)),
            'active' => $until ? 'widoczny '.self::describe(null, $until) : 'widoczny '.self::describe($from, null),
            default => null,
        };
    }

    /** Owija HTML bloku znacznikami okna; $seq musi być unikalny w obrębie jednego renderowania. */
    public static function wrap(string $html, ?string $from, ?string $until, int $seq): string
    {
        return '<!--be-okno:'.$seq.' od='.($from ?? '').' do='.($until ?? '').'-->'.$html.'<!--/be-okno:'.$seq.'-->';
    }

    /** Usuwa z HTML bloki spoza okna widoczności, a widocznym zdejmuje znaczniki. */
    public static function filter(string $html, ?CarbonImmutable $now = null): string
    {
        if (! str_contains($html, '<!--be-okno:')) {
            return $html;
        }
        $now ??= self::now();

        return preg_replace_callback(
            '~<!--be-okno:(\d+) od=([0-9T:\-]*) do=([0-9T:\-]*)-->(.*?)<!--/be-okno:\1-->~s',
            fn ($m) => in_array(self::state(self::clean($m[2]), self::clean($m[3]), $now), ['scheduled', 'expired'], true)
                ? ''
                : self::filter($m[4], $now),
            $html,
        ) ?? $html;
    }
}
