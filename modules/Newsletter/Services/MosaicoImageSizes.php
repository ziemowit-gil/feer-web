<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

/**
 * Wymagane wymiary obrazków w blokach szablonu Mosaico (public/vendor/mosaico/templates/{nazwa}).
 * Czyta HTML szablonu: obrazki `data-ko-editable="….src"` z atrybutami width/height oraz opcje
 * „Image Size” bloku. Stała wysokość = atrybut height; bez niego wysokość jest dowolna.
 */
class MosaicoImageSizes
{
    /** Polskie nazwy bloków tam, gdzie szablon ma etykietę po angielsku. */
    private const LABELS = [
        'logoBlock' => 'Logo', 'sideArticleBlock' => 'Obraz + tekst', 'sideImageBlock' => 'Duży obraz + tekst',
        'singleArticleBlock' => 'Artykuł (obraz nad tekstem)', 'doubleArticleBlock' => '2 kolumny', 'tripleArticleBlock' => '3 kolumny',
        'imageBlock' => 'Obraz', 'doubleImageBlock' => 'Galeria: 2 obrazy', 'tripleImageBlock' => 'Galeria: 3 obrazy',
        'latestNewsBlock' => 'Najnowsze aktualności (z CMS)', 'bigSocialBlock' => 'Media społecznościowe (duże)', 'socialBlock' => 'Media społecznościowe',
    ];

    private const FIELDS = ['image' => '', 'leftImage' => 'lewy', 'middleImage' => 'środkowy', 'rightImage' => 'prawy'];

    /** @return array<string, string> nazwa katalogu => nazwa do wyświetlenia */
    public static function templates(): array
    {
        $out = [];
        foreach (glob(public_path('vendor/mosaico/templates/*/template-*.html')) ?: [] as $file) {
            $out[basename(dirname($file))] = strtoupper(basename(dirname($file)));
        }

        return $out;
    }

    /**
     * @return list<array{block:string, label:string, rows:list<array{fields:list<string>, widths:list<int>, heights:list<int>, freeHeight:bool, options:list<int>}>}>
     */
    public static function forTemplate(string $template): array
    {
        $file = public_path("vendor/mosaico/templates/{$template}/template-{$template}.html");
        if (! preg_match('/^[a-z0-9-]+$/', $template) || ! is_file($file)) {
            return [];
        }
        $html = (string) file_get_contents($file);

        $options = [];
        if (preg_match_all('/(\w+)\s+imageWidth\s*\{[^}]*?options:\s*([^;}]+)/', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as [, $block, $opts]) {
                $options[$block] = array_map('intval', array_map(fn ($o) => explode('=', trim($o))[0], explode('|', $opts)));
            }
        }

        // Podział na bloki: od jednego data-ko-block do następnego.
        $parts = preg_split('/(?=data-ko-block=")/', $html) ?: [];
        $blocks = [];
        foreach ($parts as $part) {
            if (! preg_match('/^data-ko-block="(\w+)"/', $part, $bm) || isset($blocks[$bm[1]])) {
                continue;
            }
            $key = $bm[1];
            $images = [];
            if (preg_match_all('/<img\b[^>]*data-ko-editable="(\w+)\.src"[^>]*>/', $part, $im, PREG_SET_ORDER)) {
                foreach ($im as [$tag, $field]) {
                    $width = preg_match('/\swidth="(\d+)"/', $tag, $w) ? (int) $w[1] : 0;
                    if (! $width) {
                        continue;
                    }
                    $height = preg_match('/\sheight="(\d+)"/', $tag, $h) ? (int) $h[1] : null;
                    $variable = str_contains($tag, '-ko-attr-width') && isset($options[$key]);
                    $images[] = ['field' => self::FIELDS[$field] ?? $field, 'width' => $width, 'height' => $height, 'options' => $variable ? $options[$key] : []];
                }
            }
            if ($images) {
                $blocks[$key] = ['block' => $key, 'label' => self::LABELS[$key] ?? self::labelFromTemplate($html, $key), 'rows' => self::rows($images)];
            }
        }

        return array_values($blocks);
    }

    /**
     * Łączy warianty obrazka (ze stałą wysokością i bez, z odstępami i bez) w jeden wiersz na pole,
     * a pola o identycznych wymaganiach (np. lewy/środkowy/prawy) w jeden wiersz.
     */
    private static function rows(array $images): array
    {
        $byField = [];
        foreach ($images as $img) {
            $f = &$byField[$img['field']];
            $f ??= ['widths' => [], 'heights' => [], 'freeHeight' => false, 'options' => []];
            $f['widths'][] = $img['width'];
            $img['height'] ? $f['heights'][] = $img['height'] : $f['freeHeight'] = true;
            $f['options'] = array_values(array_unique(array_merge($f['options'], $img['options'])));
            unset($f);
        }
        $rows = [];
        foreach ($byField as $field => $spec) {
            $spec['widths'] = array_values(array_unique($spec['widths']));
            rsort($spec['widths']);
            $spec['heights'] = array_values(array_unique($spec['heights']));
            sort($spec['options']);
            $sig = json_encode($spec);
            $rows[$sig] ??= ['fields' => []] + $spec;
            if ($field !== '') {
                $rows[$sig]['fields'][] = $field;
            }
        }

        return array_values($rows);
    }

    private static function labelFromTemplate(string $html, string $block): string
    {
        return preg_match('/' . preg_quote($block, '/') . '\s*\{\s*label:\s*([^;}]+)/', $html, $m) ? trim($m[1]) : $block;
    }
}
