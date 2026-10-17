<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Skrót błędów z laravel.log dla pulpitu „Kondycja serwisu”: czyta tylko koniec pliku (log ma setki tysięcy
 * linii), bierze wpisy ERROR/CRITICAL/ALERT/EMERGENCY z ostatnich N godzin i grupuje je po znormalizowanym
 * komunikacie (liczby i identyfikatory → #), z liczbą wystąpień, ostatnim wystąpieniem i pierwszą ramką z app/.
 */
class LogDigest
{
    public const TAIL_BYTES = 5 * 1024 * 1024;

    public const LEVELS = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    /** Rozmiar logu, przy którym podpowiadamy rotację (LOG_CHANNEL=daily). */
    public const ROTATE_HINT_BYTES = 50 * 1024 * 1024;

    /**
     * @return array{exists: bool, size: int, total: int, groups: list<array{message: string, level: string, count: int, last: Carbon, frame: ?string}>, truncated: bool}
     */
    public static function read(?string $path = null, int $hours = 24, int $limit = 10, int $tailBytes = self::TAIL_BYTES): array
    {
        $path ??= storage_path('logs/laravel.log');
        if (! is_file($path) || ! is_readable($path)) {
            return ['exists' => false, 'size' => 0, 'total' => 0, 'groups' => [], 'truncated' => false];
        }

        $size = (int) filesize($path);
        $text = self::tail($path, $size, $tailBytes);
        $since = now()->subHours($hours);
        $groups = [];
        $total = 0;

        // Wpis zaczyna się od „[2026-10-17 15:06:29] local.ERROR: …”, ślad stosu to kolejne linie do następnego wpisu.
        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/m', $text) ?: [];
        foreach ($parts as $entry) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})[^\]]*\]\s+[\w.-]+\.([A-Z]+):\s?(.*)$/m', $entry, $m)) {
                continue;
            }
            if (! in_array($m[3], self::LEVELS, true)) {
                continue;
            }
            try {
                // Laravel zapisuje czas w logu w strefie aplikacji.
                $at = Carbon::parse($m[1].' '.$m[2], config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }
            if ($at->lt($since)) {
                continue;
            }

            $total++;
            $message = self::normalize($m[4]);
            $key = md5($m[3].'|'.$message);
            if (! isset($groups[$key])) {
                $groups[$key] = ['message' => $message, 'level' => $m[3], 'count' => 0, 'last' => $at, 'frame' => self::appFrame($entry)];
            }
            $groups[$key]['count']++;
            if ($at->gt($groups[$key]['last'])) {
                $groups[$key]['last'] = $at;
            }
        }

        usort($groups, fn ($a, $b) => [$b['count'], $b['last']->getTimestamp()] <=> [$a['count'], $a['last']->getTimestamp()]);

        return [
            'exists' => true,
            'size' => $size,
            'total' => $total,
            'groups' => array_slice($groups, 0, $limit),
            'truncated' => $size > $tailBytes,
        ];
    }

    /** Ile archiwów wyczyszczonego logu trzymamy (storage/logs/laravel-archiwum-*.log.gz). */
    public const KEEP_ARCHIVES = 5;

    /**
     * „Wyczyść” na pulpicie: zapisuje bieżący log jako skompresowane archiwum obok i opróżnia plik
     * (opróżnienie, nie usunięcie — procesy z otwartym plikiem piszą dalej). Zostawia KEEP_ARCHIVES ostatnich archiwów.
     *
     * @return array{archive: ?string, bytes: int}
     */
    public static function clear(?string $path = null): array
    {
        $path ??= storage_path('logs/laravel.log');
        if (! is_file($path) || filesize($path) === 0) {
            return ['archive' => null, 'bytes' => 0];
        }

        $bytes = (int) filesize($path);
        $archive = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'-archiwum-'.now()->format('Ymd-His').'.log.gz';
        $in = fopen($path, 'rb');
        $out = gzopen($archive, 'wb6');
        if (! $in || ! $out) {
            throw new \RuntimeException('Nie udało się zapisać archiwum logu — niczego nie wyczyszczono.');
        }
        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1 << 20));
        }
        fclose($in);
        gzclose($out);

        file_put_contents($path, '');

        $archives = glob(dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'-archiwum-*.log.gz') ?: [];
        rsort($archives);
        foreach (array_slice($archives, self::KEEP_ARCHIVES) as $old) {
            @unlink($old);
        }

        return ['archive' => $archive, 'bytes' => $bytes];
    }

    /** Ostatnie $bytes bajtów pliku, od początku pełnej linii. */
    private static function tail(string $path, int $size, int $bytes): string
    {
        $fh = fopen($path, 'rb');
        if (! $fh) {
            return '';
        }
        $offset = max(0, $size - $bytes);
        fseek($fh, $offset);
        $text = (string) stream_get_contents($fh);
        fclose($fh);
        if ($offset > 0 && ($nl = strpos($text, "\n")) !== false) {
            $text = substr($text, $nl + 1);
        }

        return $text;
    }

    /** Komunikat bez kontekstu JSON i zmiennych części (liczby, identyfikatory) — do grupowania. */
    public static function normalize(string $message): string
    {
        $message = preg_replace('/\s\{".*$|\s\[\]\s*\[\]\s*$|\s\{"exception".*$/s', '', $message) ?? $message;
        $message = preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i', '#', $message) ?? $message;
        $message = preg_replace('/\d+/', '#', $message) ?? $message;
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        return mb_strlen($message) > 200 ? mb_substr($message, 0, 199).'…' : $message;
    }

    /** Pierwsza ramka śladu stosu z kodu aplikacji (app/, modules/, database/), np. „app/Models/Page.php:404”. */
    private static function appFrame(string $entry): ?string
    {
        if (preg_match('~[/\\\\]((?:app|modules|database|resources)[/\\\\][^\s():]+\.php)[:(](\d+)~', $entry, $m)) {
            return str_replace('\\', '/', $m[1]).':'.$m[2];
        }

        return null;
    }
}
