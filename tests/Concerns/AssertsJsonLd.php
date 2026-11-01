<?php

namespace Tests\Concerns;

use Illuminate\Support\Str;

/**
 * Wyciąganie i sprawdzanie bloków `<script type="application/ld+json">` z HTML-a strony.
 * Każdy blok musi być poprawnym JSON-em z `@context` = https://schema.org (patrz StructuredDataJsonLdTest).
 */
trait AssertsJsonLd
{
    /**
     * Dekoduje wszystkie bloki ld+json ze strony; każdy musi być poprawnym JSON-em i mieć `@context`.
     *
     * @return list<array<string, mixed>>
     */
    protected function jsonLdBlocks(string $html): array
    {
        preg_match_all('~<script\b[^>]*\btype="application/ld\+json"[^>]*>(.*?)</script>~s', $html, $matches);

        $blocks = [];
        foreach ($matches[1] as $i => $raw) {
            $n = $i + 1;
            $block = json_decode($raw, true);

            $this->assertSame(JSON_ERROR_NONE, json_last_error(), "Blok ld+json #{$n} nie jest poprawnym JSON-em (".json_last_error_msg().'): '.Str::limit(trim($raw), 160));
            $this->assertIsArray($block, "Blok ld+json #{$n} nie jest obiektem JSON.");
            $this->assertSame('https://schema.org', $block['@context'] ?? null, "Blok ld+json #{$n} nie ma @context = https://schema.org, klucze: ".implode(', ', array_map(fn ($k) => Str::limit((string) $k, 40), array_keys($block))));
            $this->assertStringContainsString('"@context":"https://schema.org"', $raw, "Blok ld+json #{$n} nie ma dosłownego klucza @context.");

            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * Sprawdza, że strona ma bloki ld+json podanych typów schema.org (i żadnego uszkodzonego),
     * oraz że do HTML-a nie wycieka kod skompilowanej dyrektywy Blade.
     *
     * @param  list<string>  $types  oczekiwane wartości `@type`, np. ['Organization', 'Event']
     * @return array<string, array<string, mixed>> bloki indeksowane typem
     */
    protected function assertJsonLdTypes(string $html, array $types): array
    {
        $this->assertStringNotContainsString('__contextArgs', $html, 'W HTML wyciekł kod skompilowanej dyrektywy @context.');

        $byType = [];
        foreach ($this->jsonLdBlocks($html) as $block) {
            $byType[(string) ($block['@type'] ?? '')] = $block;
        }

        foreach ($types as $type) {
            $this->assertArrayHasKey($type, $byType, "Brak bloku ld+json typu {$type}; są: ".implode(', ', array_keys($byType)));
        }

        return $byType;
    }
}
