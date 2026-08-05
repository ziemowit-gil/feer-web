<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HealthCheckController extends Controller
{
    public function index(): View
    {
        $checks = collect([
            $this->checkDatabase(),
            $this->checkCache(),
            $this->checkStorage(),
            $this->checkFailedJobs(),
            $this->checkSzo(),
            $this->checkPendingApprovals(),
        ]);

        $overall = $checks->contains(fn ($c) => $c['status'] === 'error') ? 'error'
            : ($checks->contains(fn ($c) => $c['status'] === 'warning') ? 'warning' : 'ok');

        return view('admin.health.index', compact('checks', 'overall'));
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            $size = $this->dbSizeKb();

            return [
                'name'   => 'Baza danych',
                'icon'   => 'fa-database',
                'status' => 'ok',
                'detail' => $size !== null ? number_format($size / 1024, 1, ',', ' ').' MB' : 'połączono',
            ];
        } catch (\Throwable $e) {
            return ['name' => 'Baza danych', 'icon' => 'fa-database', 'status' => 'error', 'detail' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.time();
            Cache::put($key, 'ok', 5);
            $hit = Cache::get($key) === 'ok';
            Cache::forget($key);

            return ['name' => 'Cache', 'icon' => 'fa-bolt', 'status' => $hit ? 'ok' : 'error', 'detail' => $hit ? 'odczyt/zapis działa' : 'zapis się nie powiódł'];
        } catch (\Throwable $e) {
            return ['name' => 'Cache', 'icon' => 'fa-bolt', 'status' => 'error', 'detail' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $path = storage_path('app');

        if (! is_dir($path) || ! is_writable($path)) {
            return ['name' => 'Dysk (storage)', 'icon' => 'fa-hard-drive', 'status' => 'error', 'detail' => 'katalog storage/app niedostępny'];
        }

        $free  = disk_free_space($path);
        $total = disk_total_space($path);

        if ($free === false || $total === false) {
            return ['name' => 'Dysk (storage)', 'icon' => 'fa-hard-drive', 'status' => 'warning', 'detail' => 'nie można sprawdzić wolnego miejsca'];
        }

        $freeMb  = round($free / 1024 / 1024);
        $percent = $total > 0 ? round((1 - $free / $total) * 100) : 0;
        $status  = $freeMb < 100 ? 'error' : ($freeMb < 500 ? 'warning' : 'ok');

        return [
            'name'   => 'Dysk (storage)',
            'icon'   => 'fa-hard-drive',
            'status' => $status,
            'detail' => "wolne: {$freeMb} MB ({$percent}% zajęte)",
        ];
    }

    private function checkFailedJobs(): array
    {
        try {
            $count = DB::table('failed_jobs')->count();
            $status = $count > 10 ? 'error' : ($count > 0 ? 'warning' : 'ok');

            return [
                'name'   => 'Nieudane zadania (queue)',
                'icon'   => 'fa-clock-rotate-left',
                'status' => $status,
                'detail' => $count === 0 ? 'brak' : "{$count} nieudanych zadań",
            ];
        } catch (\Throwable) {
            return ['name' => 'Nieudane zadania (queue)', 'icon' => 'fa-clock-rotate-left', 'status' => 'warning', 'detail' => 'tabela failed_jobs niedostępna'];
        }
    }

    private function checkSzo(): array
    {
        $settings = SiteSetting::current();

        if (! $settings->szoConfigured()) {
            return ['name' => 'SZO (komunikaty)', 'icon' => 'fa-plug', 'status' => 'warning', 'detail' => 'adres SZO nie jest skonfigurowany'];
        }

        $url = $settings->szoKomunikatyUrl();

        try {
            $response = Http::connectTimeout(5)->timeout(8)->get($url);
            $status   = $response->successful() ? 'ok' : 'error';
            $detail   = $response->successful()
                ? "HTTP {$response->status()}"
                : "HTTP {$response->status()} — {$url}";

            return ['name' => 'SZO (komunikaty)', 'icon' => 'fa-plug', 'status' => $status, 'detail' => $detail];
        } catch (\Throwable $e) {
            return ['name' => 'SZO (komunikaty)', 'icon' => 'fa-plug', 'status' => 'error', 'detail' => $e->getMessage()];
        }
    }

    private function checkPendingApprovals(): array
    {
        try {
            $count = DB::table('content_approvals')->where('status', 'pending')->count();
            $status = $count > 20 ? 'warning' : 'ok';

            return [
                'name'   => 'Treści do zatwierdzenia',
                'icon'   => 'fa-clipboard-check',
                'status' => $status,
                'detail' => $count === 0 ? 'kolejka pusta' : "{$count} oczekujących",
            ];
        } catch (\Throwable) {
            return ['name' => 'Treści do zatwierdzenia', 'icon' => 'fa-clipboard-check', 'status' => 'ok', 'detail' => 'brak danych'];
        }
    }

    private function dbSizeKb(): ?int
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $path = DB::connection()->getDatabaseName();

                return file_exists($path) ? (int) ceil(filesize($path) / 1024) : null;
            }

            if ($driver === 'mysql') {
                $db  = DB::connection()->getDatabaseName();
                $row = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$db]);

                return isset($row[0]->size) ? (int) $row[0]->size : null;
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
