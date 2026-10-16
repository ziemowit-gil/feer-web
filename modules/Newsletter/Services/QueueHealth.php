<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stan kolejki (QUEUE_CONNECTION=database): ile zadań czeka, najstarsze, nieudane
 * i kiedy worker ostatnio pracował (tętno zapisywane w Queue::looping).
 * Na hostingu bez supervisora worker startuje co minutę z harmonogramu.
 */
class QueueHealth
{
    public const HEARTBEAT_KEY = 'queue:worker-heartbeat';

    /** Zadanie gotowe do wykonania (available_at minęło) i czekające dłużej niż tyle minut = problem. Odłożone celowo (limit, okno wysyłki) się nie liczą. */
    public const STUCK_AFTER_MINUTES = 5;

    /** Brak tętna workera dłużej niż tyle minut = worker nie działa. */
    public const WORKER_SILENT_MINUTES = 5;

    public static function beat(): void
    {
        Cache::put(self::HEARTBEAT_KEY, now()->getTimestamp(), now()->addDay());
    }

    public function lastWorkerRun(): ?Carbon
    {
        $ts = Cache::get(self::HEARTBEAT_KEY);

        return $ts ? Carbon::createFromTimestamp((int) $ts) : null;
    }

    /**
     * @return array{queues: array<string, array{pending: int, oldest: ?Carbon}>, pending: int, oldest: ?Carbon, failed: int, failed_recent: int, last_failed_at: ?Carbon, worker: ?Carbon, problems: list<string>, ok: bool}
     */
    public function snapshot(): array
    {
        $queues = [];
        $pending = 0;
        $oldest = null;
        if (Schema::hasTable('jobs')) {
            foreach (DB::table('jobs')->selectRaw('queue, COUNT(*) c, MIN(CASE WHEN available_at <= ? THEN available_at END) oldest', [now()->getTimestamp()])->groupBy('queue')->get() as $row) {
                $o = $row->oldest ? Carbon::createFromTimestamp((int) $row->oldest) : null;
                $queues[$row->queue] = ['pending' => (int) $row->c, 'oldest' => $o];
                $pending += (int) $row->c;
                if ($o && (! $oldest || $o->lt($oldest))) {
                    $oldest = $o;
                }
            }
        }

        $failed = 0;
        $failedRecent = 0;
        $lastFailed = null;
        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
            $failedRecent = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
            $last = DB::table('failed_jobs')->max('failed_at');
            $lastFailed = $last ? Carbon::parse($last) : null;
        }

        $worker = $this->lastWorkerRun();
        $problems = [];
        if ($oldest && $oldest->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES))) {
            $problems[] = sprintf('Zadania czekają w kolejce od %s (%d szt.) — worker ich nie przetwarza.', $oldest->diffForHumans(), $pending);
        }
        if ($oldest && (! $worker || $worker->lt(now()->subMinutes(self::WORKER_SILENT_MINUTES)))) {
            $problems[] = $worker
                ? 'Worker kolejki ostatnio pracował ' . $worker->diffForHumans() . '. Sprawdź cron z „schedule:run”.'
                : 'Brak śladu pracy workera kolejki. Sprawdź cron z „schedule:run”.';
        }
        if ($failedRecent > 0) {
            $problems[] = "Nieudane zadania w ostatniej dobie: {$failedRecent}.";
        }

        return [
            'queues' => $queues, 'pending' => $pending, 'oldest' => $oldest, 'failed' => $failed,
            'failed_recent' => $failedRecent, 'last_failed_at' => $lastFailed, 'worker' => $worker,
            'problems' => $problems, 'ok' => $problems === [],
        ];
    }
}
