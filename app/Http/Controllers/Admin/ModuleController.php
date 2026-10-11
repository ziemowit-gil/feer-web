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
