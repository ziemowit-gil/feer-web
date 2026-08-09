<?php

declare(strict_types=1);

namespace App\Modules;

final class HookManager
{
    /** @var array<string, array<int, list<callable>>> */
    private array $actions = [];

    /** @var array<string, array<int, list<callable>>> */
    private array $filters = [];

    // ── Actions ───────────────────────────────────────────────────────

    public function addAction(string $tag, callable $callback, int $priority = 10): void
    {
        $this->actions[$tag][$priority][] = $callback;
    }

    public function doAction(string $tag, mixed ...$args): void
    {
        if (! isset($this->actions[$tag])) {
            return;
        }

        ksort($this->actions[$tag]);

        foreach ($this->actions[$tag] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function hasAction(string $tag): bool
    {
        return ! empty($this->actions[$tag]);
    }

    public function removeAction(string $tag, callable $callback, int $priority = 10): void
    {
        $this->removeFromRegistry($this->actions, $tag, $callback, $priority);
    }

    // ── Filters ───────────────────────────────────────────────────────

    public function addFilter(string $tag, callable $callback, int $priority = 10): void
    {
        $this->filters[$tag][$priority][] = $callback;
    }

    /**
     * Przepuszcza $value przez łańcuch callbacków sortowanych wg priorytetu.
     * Każdy callback musi przyjąć ($value, ...$args) i zwrócić (zmodyfikowaną) wartość.
     */
    public function applyFilters(string $tag, mixed $value, mixed ...$args): mixed
    {
        if (! isset($this->filters[$tag])) {
            return $value;
        }

        ksort($this->filters[$tag]);

        foreach ($this->filters[$tag] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public function hasFilter(string $tag): bool
    {
        return ! empty($this->filters[$tag]);
    }

    public function removeFilter(string $tag, callable $callback, int $priority = 10): void
    {
        $this->removeFromRegistry($this->filters, $tag, $callback, $priority);
    }

    // ── Internal ──────────────────────────────────────────────────────

    /** @param array<string, array<int, list<callable>>> $registry */
    private function removeFromRegistry(array &$registry, string $tag, callable $callback, int $priority): void
    {
        if (! isset($registry[$tag][$priority])) {
            return;
        }

        $registry[$tag][$priority] = array_values(
            array_filter($registry[$tag][$priority], fn ($c) => $c !== $callback)
        );
    }
}
