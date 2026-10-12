<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\BackupRun;
use App\Http\Controllers\Controller;
use App\Support\Backup\BackupManager;
use App\Support\Backup\BackupSettings;
use App\Support\DeployHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Panel admin: kopie zapasowe — tworzenie, pobieranie, wgrywanie, przywracanie,
 * harmonogram i serwer zdalny (FTP/SFTP). Logika w App\Support\Backup\BackupManager.
 */
class BackupController extends Controller
{
    public function index(BackupManager $manager): View
    {
        $settings = BackupSettings::load();
        $dir = $manager->directory();

        return view('admin.backups.index', [
            'backups' => $manager->list(),
            'settings' => $settings,
            'lastResult' => Cache::get(BackupRun::LAST_RESULT_KEY),
            'scheduler' => app(DeployHealth::class)->checkScheduler(),
            'freeSpace' => @disk_free_space($dir) ?: null,
            'uploadLimit' => $this->uploadLimit(),
        ]);
    }

    public function store(Request $request, BackupManager $manager): RedirectResponse
    {
        $settings = BackupSettings::load();

        try {
            $name = $manager->create('manual', $request->boolean('include_files'));
            $manager->prune();

            $message = "Utworzono kopię {$name}.";
            if ($request->boolean('upload') && $settings->remoteEnabled()) {
                $manager->upload($name);
                $message .= ' Wysłano na serwer zdalny.';
            }

            Cache::forever(BackupRun::LAST_RESULT_KEY, ['ok' => true, 'at' => now()->toIso8601String(), 'message' => $name]);

            return back()->with('status', $message);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => 'Nie udało się utworzyć kopii: '.$e->getMessage()]);
        }
    }

    public function download(string $name, BackupManager $manager): BinaryFileResponse
    {
        try {
            $path = $manager->pathFor($name);
        } catch (\RuntimeException) {
            abort(404);
        }

        activity('cms')->causedBy(auth()->user())->event('backup_downloaded')
            ->withProperty('label', $name)->log('Backup downloaded');

        return response()->download($path, $name, ['Content-Type' => 'application/zip']);
    }

    public function upload(Request $request, BackupManager $manager): RedirectResponse
    {
        $request->validate([
            'archive' => ['required', 'file', 'mimetypes:application/zip,application/x-zip-compressed,application/octet-stream'],
        ], [
            'archive.required' => 'Wybierz plik ZIP z kopią.',
            'archive.uploaded' => 'Plik jest za duży dla ustawień serwera (limit: '.$this->uploadLimit().').',
        ]);

        try {
            $name = $manager->import($request->file('archive')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['archive' => $e->getMessage()]);
        }

        return back()->with('status', "Wgrano kopię jako {$name}. Możesz ją teraz przywrócić.");
    }

    public function restore(Request $request, string $name, BackupManager $manager): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:PRZYWRÓĆ'],
        ], [
            'confirmation.*' => 'Aby przywrócić kopię, wpisz słowo PRZYWRÓĆ.',
        ]);

        try {
            $safety = $manager->restore($name, $request->boolean('restore_files'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => 'Przywracanie nie powiodło się: '.$e->getMessage()]);
        }

        return redirect()->route('admin.kopie.index')
            ->with('status', "Przywrócono kopię {$name}. Stan sprzed przywrócenia zapisano jako {$safety}.");
    }

    public function destroy(string $name, BackupManager $manager): RedirectResponse
    {
        try {
            $manager->delete($name);
        } catch (\RuntimeException) {
            abort(404);
        }

        return back()->with('status', "Usunięto kopię {$name}.");
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'schedule' => ['required', Rule::in(array_keys(BackupSettings::SCHEDULES))],
            'time' => ['required', 'date_format:H:i'],
            'keep' => ['required', 'integer', 'min:1', 'max:90'],
            'remote_type' => ['required', Rule::in(array_keys(BackupSettings::REMOTE_TYPES))],
            'remote_host' => ['nullable', 'required_unless:remote_type,none', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'remote_port' => ['nullable', 'integer', 'between:1,65535'],
            'remote_username' => ['nullable', 'required_unless:remote_type,none', 'string', 'max:255'],
            'remote_password' => ['nullable', 'string', 'max:255'],
            'remote_root' => ['nullable', 'string', 'max:255'],
            'remote_keep' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'remote_host.regex' => 'Podaj samą nazwę serwera, bez protokołu i ścieżki (np. backup.example.org).',
            'remote_host.required_unless' => 'Podaj adres serwera zdalnego.',
            'remote_username.required_unless' => 'Podaj nazwę użytkownika serwera zdalnego.',
        ]);

        $data['include_files'] = $request->boolean('include_files');
        $data['remote_ssl'] = $request->boolean('remote_ssl');
        $data['remote_password_clear'] = $request->boolean('remote_password_clear');
        $data['remote_host'] ??= '';
        $data['remote_username'] ??= '';
        $data['remote_root'] = $data['remote_root'] ?: '/';

        BackupSettings::load()->save($data);

        return back()->with('status', 'Zapisano ustawienia kopii zapasowych.');
    }

    public function testRemote(): RedirectResponse
    {
        try {
            (new BackupManager)->testRemote();
        } catch (\Throwable $e) {
            return back()->withErrors(['remote' => 'Połączenie nieudane: '.$e->getMessage()]);
        }

        return back()->with('status', 'Połączenie z serwerem zdalnym działa (zapis i usunięcie pliku testowego).');
    }

    private function uploadLimit(): string
    {
        return collect([ini_get('upload_max_filesize'), ini_get('post_max_size')])
            ->sortBy(fn ($v) => (int) $v * match (strtoupper(substr($v, -1))) { 'G' => 1 << 30, 'M' => 1 << 20, 'K' => 1 << 10, default => 1 })
            ->first();
    }
}
