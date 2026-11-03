<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Facades\Route;

/** Wspólne reguły adresów subwitryn: postać porównywana przez ResolveCurrentSite i adresy zarezerwowane. */
final class SiteAddresses
{
    /** Adres (adres skrócony albo domena) tak, jak porównuje go ResolveCurrentSite: bez spacji, małymi literami; pusty → null. */
    public static function normalize(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }

    /**
     * Adresy skrócone, które nie mogą być prefiksem witryny (zlałyby się z trasą albo panelem).
     *
     * @return list<string>
     */
    public static function reservedSlugs(?string $kind = null): array
    {
        // Prefiks witryny to pierwszy segment adresu, więc nie może się zlać z żadną trasą ani panelem.
        $routeSegments = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => strtolower(explode('/', trim($route->uri(), '/'))[0] ?? ''))
            ->filter(fn ($segment) => $segment !== '' && ! str_contains($segment, '{'))
            ->unique()->values()->all();
        $reserved = array_values(array_unique(array_merge(Page::RESERVED_SLUGS, ['site', (string) config('app.admin_prefix', 'admin')], $routeSegments)));
        // Witryna BIP może mieć adres /bip — pod nim i tak jest pokazywany BIP witryny nadrzędnej.
        if ($kind === 'bip') {
            $reserved = array_values(array_diff($reserved, ['bip']));
        }

        return $reserved;
    }

    /** Czy pod tym adresem skróconym działa na stałe coś innego (trasa, panel, blog) — wtedy nie jest on dawnym adresem witryny. */
    public static function isReservedSlug(string $slug): bool
    {
        return in_array(strtolower($slug), self::reservedSlugs(), true);
    }
}
