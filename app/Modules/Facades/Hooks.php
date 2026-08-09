<?php

declare(strict_types=1);

namespace App\Modules\Facades;

use App\Modules\HookManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void  addAction(string $tag, callable $callback, int $priority = 10)
 * @method static void  doAction(string $tag, mixed ...$args)
 * @method static bool  hasAction(string $tag)
 * @method static void  removeAction(string $tag, callable $callback, int $priority = 10)
 * @method static void  addFilter(string $tag, callable $callback, int $priority = 10)
 * @method static mixed applyFilters(string $tag, mixed $value, mixed ...$args)
 * @method static bool  hasFilter(string $tag)
 * @method static void  removeFilter(string $tag, callable $callback, int $priority = 10)
 *
 * @see HookManager
 */
final class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
