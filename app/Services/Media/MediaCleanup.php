<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Models\MediaLibrary;
use App\Models\MediaVersion;
use Illuminate\Support\Collection;

/**
 * Porządki w bibliotece: duplikaty, pliki nieużywane, za duże obrazy, zajęte miejsce.
 *
 * Duplikaty: najpierw grupujemy po rozmiarze (tanio), skrót SHA-1 liczymy tylko
 * dla plików o tym samym rozmiarze i zapisujemy go na rekordzie (media.sha1).
 */
class MediaCleanup
{
    /** Obraz cięższy niż tyle bajtów albo szerszy/wyższy niż tyle pikseli warto zmniejszyć. */
    public const LARGE_IMAGE_BYTES = 1024 * 1024;

    public const LARGE_IMAGE_PX = 2560;

    public function __construct(private MediaUsage $usage) {}

    /**
     * Grupy identycznych plików (co najmniej dwa w grupie), od największej straty miejsca.
     *
     * @param  Collection<int, Media>  $media
     * @return Collection<int, array{hash: string, items: Collection<int, Media>, wasted: int}>
     */
    public function duplicates(Collection $media): Collection
    {
        return $media->groupBy('size')
            ->filter(fn ($group, $size) => $group->count() > 1 && (int) $size > 0)
            ->flatMap(function (Collection $group) {
                $group->each(function (Media $m) {
                    if (! $m->sha1 && is_file($path = $m->getPath())) {
                        $m->forceFill(['sha1' => sha1_file($path)])->saveQuietly();
                    }
                });

                return $group->filter(fn ($m) => $m->sha1)->groupBy('sha1')->filter(fn ($g) => $g->count() > 1)->all();
            })
            ->map(fn (Collection $items, $hash) => [
                'hash' => (string) $hash,
                'items' => $items->sortBy('id')->values(),
                'wasted' => (int) $items->first()->size * ($items->count() - 1),
            ])
            ->sortByDesc('wasted')
            ->values();
    }

    /**
     * Pliki z biblioteki, do których nic się nie odwołuje (bez archiwum — tam są z założenia).
     *
     * @param  Collection<int, Media>  $media
     */
    public function unused(Collection $media): Collection
    {
        $counts = $this->usage->counts();

        return $media->filter(fn (Media $m) => $m->model_type === MediaLibrary::class
                && $m->archived_at === null
                && empty($counts[$m->id]))
            ->sortByDesc('size')
            ->values();
    }

    /**
     * Obrazy do zmniejszenia: ciężkie albo bardzo duże w pikselach (SVG pomijamy).
     *
     * @param  Collection<int, Media>  $media
     * @return Collection<int, array{media: Media, width: ?int, height: ?int, reasons: array<string>}>
     */
    public function largeImages(Collection $media): Collection
    {
        return $media->filter(fn (Media $m) => $m->isImage() && $m->mime_type !== 'image/svg+xml')
            ->map(function (Media $m) {
                $size = @getimagesize($m->getPath()) ?: [null, null];
                [$w, $h] = [$size[0] ?? null, $size[1] ?? null];
                $reasons = [];
                if ((int) $m->size > self::LARGE_IMAGE_BYTES) {
                    $reasons[] = 'waży ' . $m->human_readable_size;
                }
                if (max((int) $w, (int) $h) > self::LARGE_IMAGE_PX) {
                    $reasons[] = "{$w}×{$h} px";
                }

                return ['media' => $m, 'width' => $w, 'height' => $h, 'reasons' => $reasons];
            })
            ->filter(fn ($row) => $row['reasons'])
            ->sortByDesc(fn ($row) => (int) $row['media']->size)
            ->values();
    }

    /**
     * Zajęte miejsce: oryginały (wg typu i folderu), konwersje (miniatury, WebP) i wersje.
     *
     * @param  Collection<int, Media>  $media
     */
    public function storage(Collection $media): array
    {
        $originals = (int) $media->sum('size');

        $conversions = 0;
        foreach ($media as $m) {
            $dir = dirname($m->getPath()) . '/conversions';
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') ?: [] as $file) {
                    $conversions += (int) @filesize($file);
                }
            }
        }

        $versions = (int) MediaVersion::query()->whereIn('media_id', $media->pluck('id'))->sum('size');

        $byKind = $media->groupBy(fn (Media $m) => $m->kind())
            ->map(fn ($g) => ['count' => $g->count(), 'bytes' => (int) $g->sum('size')]);

        $byFolder = $media->groupBy(fn (Media $m) => $m->folder?->fullPath() ?? 'Katalog główny')
            ->map(fn ($g) => ['count' => $g->count(), 'bytes' => (int) $g->sum('size')])
            ->sortByDesc('bytes');

        $quotaMb = (int) config('media-library.quota_mb', 0);

        return [
            'originals' => $originals,
            'conversions' => $conversions,
            'versions' => $versions,
            'total' => $originals + $conversions + $versions,
            'count' => $media->count(),
            'by_kind' => $byKind,
            'by_folder' => $byFolder,
            'quota' => $quotaMb > 0 ? $quotaMb * 1024 * 1024 : null,
        ];
    }

    /** Szybka suma do licznika na liście plików (oryginały i wersje, bez konwersji na dysku). */
    public static function quickTotal(): int
    {
        return (int) Media::query()->sum('size') + (int) MediaVersion::query()->sum('size');
    }
}
