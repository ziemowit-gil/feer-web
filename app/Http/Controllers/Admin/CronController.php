<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CronTab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * System → Harmonogram (cron): diagnoza wpisu harmonogramu feer-web, naprawa jednym przyciskiem
 * (scripts/cron.sh), pełna edycja crontaba i przywracanie kopii. Zapis wymaga ponownego podania hasła
 * (password.confirm) i trafia do dziennika zdarzeń. Tylko administrator.
 */
class CronController extends Controller
{
    public function index(): View
    {
        $content = null;
        $error = null;
        if (CronTab::available()) {
            try {
                $content = CronTab::read();
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'PHP na serwerze ma wyłączone uruchamianie poleceń (proc_open) — użyj w konsoli: scripts/cron.sh';
        }

        return view('admin.cron.index', [
            'content' => $content,
            'error' => $error,
            'analysis' => $content !== null ? CronTab::analyze($content) : null,
            'backups' => CronTab::backups(),
        ]);
    }

    public function repair(): RedirectResponse
    {
        try {
            $out = CronTab::repair();
        } catch (Throwable $e) {
            return back()->withErrors(['cron' => $e->getMessage()]);
        }
        $this->log('Naprawiono wpis harmonogramu (scripts/cron.sh)');

        return back()->with('status', 'Harmonogram naprawiony. '.collect(explode("\n", $out))->last());
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['content' => ['present', 'nullable', 'string', 'max:20000']]);
        $content = (string) ($data['content'] ?? '');

        try {
            $backup = CronTab::write($content);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['content' => $e->getMessage()]);
        }
        $this->log('Zmieniono crontab', ['backup' => basename($backup)]);

        return back()->with('status', 'Crontab zapisany. Kopia poprzedniej wersji: '.basename($backup).'.');
    }

    public function restore(Request $request): RedirectResponse
    {
        $name = (string) $request->validate(['backup' => ['required', 'string', 'max:80']])['backup'];

        try {
            CronTab::restore($name);
        } catch (Throwable $e) {
            return back()->withErrors(['cron' => $e->getMessage()]);
        }
        $this->log('Przywrócono crontab z kopii', ['backup' => $name]);

        return back()->with('status', 'Przywrócono crontab z kopii '.$name.'.');
    }

    private function log(string $message, array $props = []): void
    {
        if (function_exists('activity')) {
            activity('system')->causedBy(auth()->user())->withProperties($props)->event('cron')->log($message);
        }
    }
}
