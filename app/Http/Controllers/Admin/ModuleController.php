<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ModuleManager;
use App\Support\PluginModules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

final class ModuleController extends Controller
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function index(PluginModules $plugins)
    {
        return view('admin.modules.index', [
            'cards' => $plugins->cards(),
        ]);
    }

    /**
     * Po akcji wracamy tam, skąd ją wywołano: z zakładki Ustawienia → Moduły
     * (pole return_to=settings) albo na stronę „Moduły systemu".
     */
    private function redirectAfterAction(string $message): RedirectResponse
    {
        $target = request()->input('return_to') === 'settings'
            ? redirect()->route('admin.ustawienia.edit', ['tab' => 'modules'])
            : redirect()->route('admin.moduly.index');

        return $target->with('status', $message);
    }

    /**
     * Akcja masowa w tabeli modułów instalowanych: Aktywuj / Dezaktywuj zaznaczone (`modules[]` + `action`).
     * Działa w całej instalacji. Niezainstalowanych nie aktywujemy hurtem (instalacja uruchamia migracje) —
     * są pomijane z komunikatem; instaluje się je przyciskiem w wierszu.
     */
    public function bulk(\Illuminate\Http\Request $request): RedirectResponse
    {
        $back = $request->input('return_to') === 'settings'
            ? route('admin.ustawienia.edit', ['tab' => 'modules']).'#tabela-rozszerzen'
            : route('admin.moduly.index').'#tabela-rozszerzen';

        $action = (string) $request->input('action', '');
        if (! in_array($action, ['activate', 'deactivate'], true)) {
            return redirect()->to($back)->withErrors(['modules' => 'Wybierz akcję: Aktywuj albo Dezaktywuj zaznaczone.'], 'pluginsTable');
        }

        $ids = collect((array) $request->input('modules', []))
            ->filter(fn ($id) => is_scalar($id) && $this->modules->get((string) $id) !== null)
            ->map(fn ($id) => (string) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return redirect()->to($back)->withErrors(['modules' => 'Zaznacz co najmniej jeden moduł, a potem wybierz akcję.'], 'pluginsTable');
        }

        $changed = [];
        $unchanged = [];
        $missing = [];
        foreach ($ids as $id) {
            $name = $this->modules->get($id)->name;
            $status = $this->modules->status($id);

            if ($status === null) {
                $missing[] = $name;
            } elseif ($action === 'activate' && $status !== 'active') {
                $this->modules->activate($id);
                $changed[] = $name;
            } elseif ($action === 'deactivate' && $status === 'active') {
                $this->modules->deactivate($id);
                $changed[] = $name;
            } else {
                $unchanged[] = $name;
            }
        }

        $verb = $action === 'activate' ? 'Aktywowano' : 'Dezaktywowano';
        $n = count($changed);
        $t = $n % 10;
        $h = $n % 100;
        $noun = $n === 1 ? 'moduł' : ($t >= 2 && $t <= 4 && ($h < 12 || $h > 14) ? 'moduły' : 'modułów');
        $message = $changed === []
            ? 'Bez zmian.'
            : "{$verb} {$n} {$noun}: ".implode(', ', $changed).'.';
        if ($unchanged !== []) {
            $message .= ' Już '.($action === 'activate' ? 'aktywne' : 'nieaktywne').': '.implode(', ', $unchanged).'.';
        }
        if ($missing !== []) {
            $message .= ' Niezainstalowane (zainstaluj przyciskiem w wierszu): '.implode(', ', $missing).'.';
        }

        return redirect()->to($back)->with('plugins_table_status', $message);
    }

    public function install(string $identifier): RedirectResponse
    {
        $manifest = $this->modules->get($identifier);

        if ($manifest === null) {
            return back()->with('error', "Moduł [{$identifier}] nie istnieje.");
        }

        $this->modules->install($manifest);

        if ($manifest->hasMigrations) {
            $migrationsPath = $manifest->path('database', 'migrations');
            if (is_dir($migrationsPath)) {
                Artisan::call('migrate', [
                    '--path'     => $migrationsPath,
                    '--realpath' => true,
                    '--force'    => true,
                ]);
            }
        }

        return $this->redirectAfterAction("Moduł \"{$manifest->name}\" został zainstalowany.");
    }

    public function activate(string $identifier): RedirectResponse
    {
        $manifest = $this->modules->get($identifier);

        if ($manifest === null) {
            return back()->with('error', "Moduł [{$identifier}] nie istnieje.");
        }

        $this->modules->activate($identifier);

        return $this->redirectAfterAction("Moduł \"{$manifest->name}\" został aktywowany.");
    }

    public function deactivate(string $identifier): RedirectResponse
    {
        $manifest = $this->modules->get($identifier);

        if ($manifest === null) {
            return back()->with('error', "Moduł [{$identifier}] nie istnieje.");
        }

        $this->modules->deactivate($identifier);

        return $this->redirectAfterAction("Moduł \"{$manifest->name}\" został dezaktywowany.");
    }
}
