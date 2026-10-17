<?php

namespace App\Support;

use App\Models\EducationalMaterial;
use Illuminate\Support\Facades\View;

/**
 * Lista materiałów edukacyjnych osadzana w treści: shortcode [materialy] / [materialy:grupa=nauczyciele;typ=pdf;liczba=6]
 * i blok „Materiały edukacyjne” edytora blokowego. Karty w tym samym wyglądzie co strona /materialy
 * (partials/material-card-feer): cała karta jednym linkiem, typ i Premium jako tekst pod tytułem.
 */
class MaterialList
{
    public const DEFAULT_LIMIT = 6;

    public static function parse(?string $args): array
    {
        $raw = [];
        foreach (preg_split('/[;|]/', html_entity_decode((string) $args, ENT_QUOTES | ENT_HTML5, 'UTF-8')) as $pair) {
            if (str_contains($pair, '=')) {
                [$k, $v] = array_map('trim', explode('=', $pair, 2));
                $raw[mb_strtolower($k)] = $v;
            }
        }

        return static::normalize(['group' => $raw['grupa'] ?? null, 'type' => $raw['typ'] ?? null, 'limit' => $raw['liczba'] ?? null]);
    }

    /** @return array{group: ?string, type: ?string, limit: int} */
    public static function normalize(array $a): array
    {
        $group = $a['group'] ?? null;
        $type = $a['type'] ?? null;
        $limit = (int) ($a['limit'] ?? 0);

        return [
            'group' => is_string($group) && array_key_exists($group, EducationalMaterial::TARGET_GROUPS) ? $group : null,
            'type' => is_string($type) && array_key_exists($type, EducationalMaterial::TYPES) ? $type : null,
            'limit' => $limit >= 1 ? min(24, $limit) : self::DEFAULT_LIMIT,
        ];
    }

    public static function shortcode(array $options): string
    {
        $o = static::normalize($options);
        $parts = array_filter([
            $o['group'] ? 'grupa='.$o['group'] : null,
            $o['type'] ? 'typ='.$o['type'] : null,
            $o['limit'] !== self::DEFAULT_LIMIT ? 'liczba='.$o['limit'] : null,
        ]);

        return '[materialy'.($parts ? ':'.implode(';', $parts) : '').']';
    }

    public static function render(array $options): string
    {
        $o = static::normalize($options);
        $materials = EducationalMaterial::where('is_published', true)
            ->when($o['group'], fn ($q) => $q->where('target_group', $o['group']))
            ->when($o['type'], fn ($q) => $q->where('type', $o['type']))
            ->orderBy('order')->orderBy('title')
            ->limit($o['limit'])
            ->get();

        if ($materials->isEmpty()) {
            return '';
        }

        $user = auth()->user();

        return View::make('educational-materials.embed', [
            'materials' => $materials,
            'userCanAccessPremium' => $user && $user->hasFeature('access-premium-materials'),
            'moreUrl' => route('materials.index'),
        ])->render();
    }
}
