<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Minimalny dekoder CBOR (RFC 8949) na potrzeby WebAuthn: liczby, ciągi bajtów i tekstu,
 * tablice, mapy, true/false/null. Tylko długości określone — tyle wystarcza dla
 * attestationObject i kluczy COSE. Ciągi bajtów zwracamy jako string (binarny).
 */
class Cbor
{
    /** Dekoduje jedną wartość od pozycji $offset (przesuwa offset za nią). */
    public static function decode(string $data, int &$offset = 0): mixed
    {
        if ($offset >= strlen($data)) {
            throw new InvalidArgumentException('CBOR: koniec danych.');
        }
        $initial = ord($data[$offset++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        if ($major === 7) {
            return match ($info) {
                20 => false,
                21 => true,
                22, 23 => null,
                default => throw new InvalidArgumentException('CBOR: nieobsługiwana wartość prosta.'),
            };
        }

        $length = self::length($data, $offset, $info);

        return match ($major) {
            0 => $length,
            1 => -1 - $length,
            2, 3 => self::take($data, $offset, $length),
            4 => self::array($data, $offset, $length),
            5 => self::map($data, $offset, $length),
            6 => self::decode($data, $offset), // znacznik — zwracamy zawartość
        };
    }

    private static function length(string $data, int &$offset, int $info): int
    {
        if ($info < 24) {
            return $info;
        }
        $bytes = match ($info) {
            24 => 1, 25 => 2, 26 => 4, 27 => 8,
            default => throw new InvalidArgumentException('CBOR: nieobsługiwana długość.'),
        };
        $value = 0;
        foreach (str_split(self::take($data, $offset, $bytes)) as $byte) {
            $value = ($value << 8) | ord($byte);
        }

        return $value;
    }

    private static function take(string $data, int &$offset, int $length): string
    {
        if ($offset + $length > strlen($data)) {
            throw new InvalidArgumentException('CBOR: za krótkie dane.');
        }
        $out = substr($data, $offset, $length);
        $offset += $length;

        return $out;
    }

    private static function array(string $data, int &$offset, int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = self::decode($data, $offset);
        }

        return $out;
    }

    private static function map(string $data, int &$offset, int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $key = self::decode($data, $offset);
            $out[$key] = self::decode($data, $offset);
        }

        return $out;
    }
}
