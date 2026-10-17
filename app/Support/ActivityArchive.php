<?php

namespace App\Support;

use App\Models\Activity;
use Illuminate\Support\Carbon;

/**
 * Archiwum Dziennika zdarzeń: wpisy starsze niż N dni trafiają do plików tekstowych (jeden na miesiąc,
 * storage/app/activity-archive/dziennik-RRRR-MM.txt) i znikają z bazy. Wpisy rejestru zmian BIP zostają w bazie —
 * BIP pokazuje z nich historię dokumentów.
 */
class ActivityArchive
{
    public const DAYS = 30;

    public static function dir(): string
    {
        return storage_path('app/activity-archive');
    }

    /** @return array{archived: int, files: list<string>} */
    public static function run(int $days = self::DAYS): array
    {
        $cutoff = now()->subDays($days);
        $archived = 0;
        $files = [];
        if (! is_dir(self::dir())) {
            mkdir(self::dir(), 0750, true);
        }

        $query = Activity::query()
            ->where('created_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNull('subject_type')->orWhere('subject_type', 'not like', '%Bip%'))
            ->with('causer');

        $query->chunkById(500, function ($chunk) use (&$archived, &$files) {
            $byMonth = [];
            foreach ($chunk as $log) {
                $byMonth[$log->created_at->format('Y-m')][] = self::line($log);
            }
            foreach ($byMonth as $month => $lines) {
                $file = self::dir().'/dziennik-'.$month.'.txt';
                if (! is_file($file)) {
                    file_put_contents($file, "# Dziennik zdarzeń — archiwum {$month}\n# Data | Kto | Zdarzenie | Treść | Szczegóły\n");
                }
                file_put_contents($file, implode("\n", $lines)."\n", FILE_APPEND | LOCK_EX);
                $files[basename($file)] = true;
            }
            Activity::whereKey($chunk->modelKeys())->delete();
            $archived += $chunk->count();
        });

        return ['archived' => $archived, 'files' => array_keys($files)];
    }

    /** Jeden wpis jako czytelna linia tekstu. */
    public static function line(Activity $log): string
    {
        $subject = trim($log->subjectLabel().($log->subject_id ? ' #'.$log->subject_id : '').($log->subject_label ? ' „'.$log->subject_label.'”' : ''));
        $props = $log->properties && $log->properties->isNotEmpty() ? json_encode($log->properties->except(['attributes', 'old'])->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $clean = fn ($v) => str_replace(["\r", "\n", '|'], [' ', ' ', '/'], (string) $v);

        return implode(' | ', array_map($clean, [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user_name ?: 'System',
            $log->eventLabel() ?: $log->description,
            $subject ?: $log->description,
            $props === '[]' || $props === '{}' ? '' : $props,
        ]));
    }

    /** @return list<array{name: string, size: int, month: string}> najnowsze pierwsze */
    public static function files(): array
    {
        $files = glob(self::dir().'/dziennik-*.txt') ?: [];
        rsort($files);

        return array_map(fn ($f) => ['name' => basename($f), 'size' => (int) filesize($f), 'month' => substr(basename($f, '.txt'), 9)], $files);
    }

    public static function path(string $name): ?string
    {
        return preg_match('/^dziennik-\d{4}-\d{2}\.txt$/', $name) && is_file(self::dir().'/'.$name) ? self::dir().'/'.$name : null;
    }
}
