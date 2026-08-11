<?php

namespace App\Http\Controllers;

use App\Models\Podcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PodcastController extends Controller
{
    public function index()
    {
        $podcasts = Podcast::published()->latest('published_at')->paginate(12);

        return view('podcasts.index', compact('podcasts'));
    }

    public function show(Podcast $podcast)
    {
        abort_unless($podcast->is_published, 404);

        $canPlay = $this->canAccess(request()->user(), $podcast);

        return view('podcasts.show', compact('podcast', 'canPlay'));
    }

    public function stream(Request $request, Podcast $podcast): StreamedResponse
    {
        abort_unless($podcast->is_published, 404);
        abort_unless($this->canAccess($request->user(), $podcast), 403);

        $media = $podcast->getFirstMedia('audio');
        abort_unless($media, 404);

        $path = $media->getPath();
        $size = filesize($path);
        $mime = $media->mime_type ?: 'audio/mpeg';

        $start = 0;
        $end = $size - 1;

        if ($request->hasHeader('Range')) {
            preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches);
            $start = (int) ($matches[1] ?? 0);
            $end = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $size - 1;
        }

        $length = $end - $start + 1;
        $status = $start > 0 ? 206 : 200;

        return response()->stream(function () use ($path, $start, $length) {
            $handle = fopen($path, 'rb');
            fseek($handle, $start);
            $remaining = $length;
            while (! feof($handle) && $remaining > 0) {
                $chunk = min(8192, $remaining);
                echo fread($handle, $chunk);
                $remaining -= $chunk;
            }
            fclose($handle);
        }, $status, [
            'Content-Type' => $mime,
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function canAccess(?object $user, Podcast $podcast): bool
    {
        if (! $podcast->is_premium) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $podcastSlug = "podcast:{$podcast->id}";

        return $user->hasFeature('access-premium-podcasts')
            || $user->hasFeature($podcastSlug);
    }
}
