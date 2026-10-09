<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

final class ChannelRegistry
{
    /** @var array<string, ChannelDriver> */
    private array $drivers = [];

    public function add(ChannelDriver $driver): void
    {
        $this->drivers[$driver->key()] = $driver;
    }

    public function get(string $key): ?ChannelDriver
    {
        return $this->drivers[$key] ?? null;
    }

    /** @return array<string, ChannelDriver> */
    public function all(): array
    {
        return $this->drivers;
    }

    /** @return array<string, string> klucz => etykieta (tylko włączone) */
    public function enabledOptions(): array
    {
        $out = [];
        foreach ($this->drivers as $k => $d) {
            if ($d->enabled()) {
                $out[$k] = $d->label();
            }
        }

        return $out;
    }
}
