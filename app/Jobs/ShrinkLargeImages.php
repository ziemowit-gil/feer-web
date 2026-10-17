<?php

namespace App\Jobs;

use App\Services\Media\MediaCleanup;
use App\Services\Media\MediaOptimizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Media;

/**
 * Zmniejsza w tle za duże obrazy z biblioteki (uruchamiane po otwarciu Multimediów, najwyżej raz na 10 min).
 * W jednym przebiegu do LIMIT obrazów — reszta przy kolejnym otwarciu.
 */
class ShrinkLargeImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const LIMIT = 15;

    public int $timeout = 600;

    public int $tries = 1;

    public function handle(MediaOptimizer $optimizer): void
    {
        $done = 0;
        $candidates = Media::query()
            ->whereIn('mime_type', MediaOptimizer::FORMATS)
            ->orderByDesc('size')
            ->cursor();

        foreach ($candidates as $media) {
            if ($done >= self::LIMIT) {
                break;
            }
            if ($optimizer->shrink($media)) {
                $done++;
            }
        }
    }
}
