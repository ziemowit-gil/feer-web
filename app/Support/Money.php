<?php

namespace App\Support;

/**
 * Formatowanie kwot w złotych na stronie, w panelu, e-mailach i PDF:
 * „60 zł”, „62,50 zł”, „12 500 zł”.
 */
final class Money
{
    /** Kwota w pełnych złotych (np. cel zbiórki). */
    public static function zl(int|float $amount): string
    {
        $amount = (float) $amount;
        $formatted = fmod($amount, 1.0) === 0.0
            ? number_format($amount, 0, ',', ' ')
            : number_format($amount, 2, ',', ' ');

        return $formatted.' zł';
    }

    /** Kwota w groszach (np. wpłata). */
    public static function grosze(int $grosze): string
    {
        return self::zl($grosze / 100);
    }
}
