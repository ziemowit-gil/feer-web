<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ModuleManager
{
    /** @var Collection<string, ModuleManifest> */
    private Collection $discovered;

    /** @var Collection<string, string>  identifier => 'active'|'inactive' */
    private Collection $statuses;

    public function __construct(
        private readonly Application $app,
        private readonly string      $modulesPath,
    ) {
        $this->discovered = new Collection();
        $this->statuses   = new Collection();
    }

    // ── Discovery ────────────────────────────────────────────────────

    public function discover(): void
    {
        $this->discovered = new Collection();

        if (! is_dir($this->modulesPath)) {
            return;
        }

        foreach (new \DirectoryIterator($this->modulesPath) as $entry) {
            if (! $entry->isDir() || $entry->isDot()) {
                continue;
            }

            $jsonPath = $entry->getPathname() . DIRECTORY_SEPARATOR . 'module.json';

            if (! file_exists($jsonPath)) {
                continue;
            }

            try {
                $data     = json_decode(file_get_contents($jsonPath), associative: true, flags: JSON_THROW_ON_ERROR);
                $manifest = ModuleManifest::fromJson($entry->getPathname(), $data);
                $this->discovered->put($manifest->identifier, $manifest);
            } catch (\Throwable $e) {
                if ($this->app->hasDebugModeEnabled()) {
                    throw new \RuntimeException(
                        "Nieprawidłowy module.json w [{$entry->getPathname()}]: {$e->getMessage()}"
                    );
                }
            }
        }
    }

    // ── Statuses from DB ─────────────────────────────────────────────

    public function loadStatuses(): void
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return;
            }

            $this->statuses = DB::table('plugins')->pluck('status', 'identifier');
        } catch (\Throwable) {
            // Baza niedostępna (np. faza instalacji). Traktujemy wszystko jako inactive.
        }
    }

    // ── Booting ──────────────────────────────────────────────────────

    public function bootActiveProviders(): void
    {
        foreach ($this->active() as $manifest) {
            if (! class_exists($manifest->provider)) {
                report(new \RuntimeException(
                    "Moduł [{$manifest->identifier}]: klasa [{$manifest->provider}] nie istnieje. Uruchom composer dump-autoload."
                ));
                continue;
            }

            $this->app->register($manifest->provider);
        }
    }

    // ── Queries ───────────────────────────────────────────────────────

    /** @return Collection<string, ModuleManifest> */
    public function all(): Collection
    {
        return $this->discovered;
    }

    /** @return Collection<string, ModuleManifest> */
    public function active(): Collection
    {
        return $this->discovered->filter(
            fn (ModuleManifest $m) => $this->statuses->get($m->identifier) === 'active'
        );
    }

    /** @return Collection<string, ModuleManifest> */
    public function inactive(): Collection
    {
        return $this->discovered->reject(
            fn (ModuleManifest $m) => $this->statuses->get($m->identifier) === 'active'
        );
    }

    public function isActive(string $identifier): bool
    {
        return $this->statuses->get($identifier) === 'active';
    }

    /** Returns 'active', 'inactive', or null when the module is not installed. */
    public function status(string $identifier): ?string
    {
        return $this->statuses->get($identifier);
    }

    public function get(string $identifier): ?ModuleManifest
    {
        return $this->discovered->get($identifier);
    }

    // ── Lifecycle ─────────────────────────────────────────────────────

    public function install(ModuleManifest $manifest): void
    {
        DB::table('plugins')->updateOrInsert(
            ['identifier' => $manifest->identifier],
            [
                'name'         => $manifest->name,
                'version'      => $manifest->version,
                'status'       => 'inactive',
                'installed_at' => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );

        $this->statuses->put($manifest->identifier, 'inactive');
    }

    public function activate(string $identifier): void
    {
        DB::table('plugins')
            ->where('identifier', $identifier)
            ->update(['status' => 'active', 'activated_at' => now(), 'updated_at' => now()]);

        $this->statuses->put($identifier, 'active');
    }

    public function deactivate(string $identifier): void
    {
        DB::table('plugins')
            ->where('identifier', $identifier)
            ->update(['status' => 'inactive', 'activated_at' => null, 'updated_at' => now()]);

        $this->statuses->put($identifier, 'inactive');
    }

    public function uninstall(string $identifier): void
    {
        DB::table('plugins')->where('identifier', $identifier)->delete();
        $this->statuses->forget($identifier);
        $this->discovered->forget($identifier);
    }
}
