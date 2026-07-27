<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blokuje puste, zniechęcające ogólniki w ogłoszeniach o wolontariacie
 * („zasada krytyczna" KCW: komunikacja ma być konkretna i partnerska).
 */
class BezOgolnikow implements ValidationRule
{
    private array $banned = [
        'potrzebujemy pomocy',
        'pomóż nam',
        'każda pomoc się liczy',
        'zapraszamy chętnych',
        'szukamy wolontariuszy do pomocy',
        'przyjdź i pomóż',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $text = mb_strtolower(is_array($value) ? implode(' ', $value) : (string) $value);

        foreach ($this->banned as $phrase) {
            if (str_contains($text, $phrase)) {
                $fail("Unikaj ogólników typu „{$phrase}”. Napisz konkretnie: co robi wolontariusz i komu to realnie pomaga.");

                return;
            }
        }
    }
}
