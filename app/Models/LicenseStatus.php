<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Singleton (jeden wiersz, id=1) — status licencji tej instalacji weCMS wobec
 * Helpdesku Centralnego. Patrz config/helpdesk.php, App\Support\Helpdesk,
 * polecenie `helpdesk:sync`.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class LicenseStatus extends Model
{
    protected $fillable = [
        'instance_id', 'valid', 'reason', 'product', 'valid_until',
        'customer_email', 'activated', 'last_checked_at', 'last_error',
    ];

    protected $casts = [
        'valid' => 'boolean',
        'activated' => 'boolean',
        'valid_until' => 'date',
        'last_checked_at' => 'datetime',
    ];

    /**
     * Jedyny wiersz tej tabeli — tworzy go (z nowym instance_id) przy
     * pierwszym użyciu, jeśli jeszcze nie istnieje.
     */
    public static function current(): self
    {
        $status = static::query()->firstOrCreate(['id' => 1]);

        if (blank($status->instance_id)) {
            $status->update(['instance_id' => (string) Str::uuid()]);
        }

        return $status;
    }

    /**
     * Czy jest w ogóle sens odpytywać Helpdesk — bez adresu/klucza w
     * config/helpdesk.php integracja jest po prostu wyłączona (jak SzoClient::enabled()).
     */
    public function configured(): bool
    {
        return filled(config('helpdesk.url')) && filled(config('helpdesk.license_key'));
    }

    /** Etykieta do wyświetlenia w panelu (patrz admin/settings, sekcja "Licencja"). */
    public function statusLabel(): string
    {
        if (! $this->configured()) {
            return 'Integracja z Helpdeskiem nieskonfigurowana';
        }

        if (! $this->activated) {
            return 'Oczekuje na pierwszą aktywację';
        }

        return match (true) {
            $this->valid => 'Licencja aktywna',
            $this->reason === 'expired' => 'Licencja wygasła',
            $this->reason === 'suspended' => 'Licencja zawieszona',
            $this->reason === 'revoked' => 'Licencja unieważniona',
            $this->reason === 'activation_limit_reached' => 'Osiągnięto limit aktywacji licencji',
            $this->reason === 'not_found' => 'Nie znaleziono licencji o podanym kluczu',
            default => 'Licencja nieważna',
        };
    }
}
