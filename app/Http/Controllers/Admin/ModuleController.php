<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

final class ModuleController extends Controller
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function index()
    {
        return view('admin.modules.index', [
            'modules' => $this->modules->all(),
        ]);
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

        return redirect()->route('admin.moduly.index')
            ->with('status', "Moduł \"{$manifest->name}\" został zainstalowany.");
    }

    public function activate(string $identifier): RedirectResponse
    {
        $manifest = $this->modules->get($identifier);

        if ($manifest === null) {
            return back()->with('error', "Moduł [{$identifier}] nie istnieje.");
        }

        $this->modules->activate($identifier);

        return redirect()->route('admin.moduly.index')
            ->with('status', "Moduł \"{$manifest->name}\" został aktywowany.");
    }

    public function deactivate(string $identifier): RedirectResponse
    {
        $manifest = $this->modules->get($identifier);

        if ($manifest === null) {
            return back()->with('error', "Moduł [{$identifier}] nie istnieje.");
        }

        $this->modules->deactivate($identifier);

        return redirect()->route('admin.moduly.index')
            ->with('status', "Moduł \"{$manifest->name}\" został dezaktywowany.");
    }
}
