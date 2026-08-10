<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $isActive = $this->modules->get($module) !== null
            ? $this->modules->isActive($module)
            : SiteSetting::current()->isModuleEnabled($module);

        if (! $isActive) {
            if ($request->routeIs('admin.*')) {
                $manifest = $this->modules->get($module);
                return response()->view('admin.modules.disabled', [
                    'moduleName'  => $manifest?->name ?? $module,
                    'identifier'  => $module,
                ]);
            }
            abort(404);
        }

        return $next($request);
    }
}
