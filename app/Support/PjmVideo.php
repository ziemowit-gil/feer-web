<?php

namespace App\Support;

/**
 * Wideo z tłumaczeniem na Polski Język Migowy (PJM): rozpoznaje adres i zwraca
 * dane do osadzenia. YouTube przez youtube-nocookie.com, Vimeo z dnt=1 — bez
 * ciasteczek śledzących, więc nie wymaga zgody z banera cookies.
 */
class PjmVideo
{
    /**
     * @return array{type: 'youtube'|'vimeo'|'file', src: string}|null
     */
    public static function parse(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.)/', '', $host);
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
            $id = match (true) {
                $host === 'youtu.be' => ltrim($path, '/'),
                isset($query['v']) => $query['v'],
                (bool) preg_match('#^/(embed|shorts|live)/([^/?]+)#', $path, $m) => $m[2],
                default => null,
            };

            return is_string($id) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)
                ? ['type' => 'youtube', 'src' => "https://www.youtube-nocookie.com/embed/{$id}?rel=0&cc_load_policy=1"]
                : null;
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            return preg_match('#/(?:video/)?(\d+)(?:/([0-9a-f]+))?#', $path, $m)
                ? ['type' => 'vimeo', 'src' => "https://player.vimeo.com/video/{$m[1]}?dnt=1".(! empty($m[2]) ? "&h={$m[2]}" : '')]
                : null;
        }

        // Plik wideo: https albo z tej samej instalacji (np. z biblioteki mediów).
        if (preg_match('/\.(mp4|webm)$/i', $path) && (str_starts_with($url, 'https://') || str_starts_with($url, url('/')))) {
            return ['type' => 'file', 'src' => $url];
        }

        return null;
    }

    public static function isValid(?string $url): bool
    {
        return self::parse($url) !== null;
    }
}
