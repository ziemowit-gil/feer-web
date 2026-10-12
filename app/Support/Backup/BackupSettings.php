<?php

namespace App\Support\Backup;

use Illuminate\Support\Facades\Crypt;

/**
 * Ustawienia kopii zapasowych: harmonogram, retencja, serwer zdalny.
 *
 * Trzymane w pliku settings.json w katalogu kopii — celowo poza bazą,
 * żeby przywrócenie starszej bazy nie wyłączyło harmonogramu ani nie
 * podmieniło danych serwera zdalnego. Hasło serwera zdalnego szyfrowane APP_KEY.
 */
class BackupSettings
{
    public const SCHEDULES = ['off' => 'Wyłączone', 'daily' => 'Codziennie', 'weekly' => 'Co tydzień (poniedziałek)'];

    public const REMOTE_TYPES = ['none' => 'Brak (tylko na tym serwerze)', 'sftp' => 'SFTP', 'ftp' => 'FTP / FTPS'];

    public const DEFAULTS = [
        'schedule' => 'off',
        'time' => '02:30',
        'keep' => 7,
        'include_files' => true,
        'remote_type' => 'none',
        'remote_host' => '',
        'remote_port' => null,
        'remote_username' => '',
        'remote_password' => '', // zaszyfrowane
        'remote_root' => '/',
        'remote_ssl' => false,
        'remote_keep' => 14,
    ];

    public function __construct(private array $values = []) {}

    public static function path(): string
    {
        return rtrim(config('backup.path'), '/').'/settings.json';
    }

    public static function load(): self
    {
        $data = [];
        $file = self::path();

        if (is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true) ?: [];
        }

        return new self(array_merge(self::DEFAULTS, array_intersect_key($data, self::DEFAULTS)));
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    /** Wartości do formularza (bez hasła). */
    public function all(): array
    {
        return array_merge($this->values, ['remote_password' => '']);
    }

    public function hasRemotePassword(): bool
    {
        return $this->values['remote_password'] !== '';
    }

    public function remotePassword(): string
    {
        if ($this->values['remote_password'] === '') {
            return '';
        }

        try {
            return Crypt::decryptString($this->values['remote_password']);
        } catch (\Throwable) {
            return ''; // inny APP_KEY (np. po przeniesieniu) — trzeba wpisać hasło ponownie
        }
    }

    /** Zapisuje ustawienia; puste hasło = bez zmian. */
    public function save(array $input): self
    {
        $values = array_merge($this->values, array_intersect_key($input, self::DEFAULTS));

        $values['remote_password'] = isset($input['remote_password']) && $input['remote_password'] !== ''
            ? Crypt::encryptString($input['remote_password'])
            : $this->values['remote_password'];

        if (! empty($input['remote_password_clear'])) {
            $values['remote_password'] = '';
        }

        $values['keep'] = max(1, (int) $values['keep']);
        $values['remote_keep'] = max(1, (int) $values['remote_keep']);
        $values['include_files'] = (bool) $values['include_files'];
        $values['remote_ssl'] = (bool) $values['remote_ssl'];
        $values['remote_port'] = $values['remote_port'] ? (int) $values['remote_port'] : null;

        $dir = dirname(self::path());
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        file_put_contents(self::path(), json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        @chmod(self::path(), 0600);

        $this->values = $values;

        return $this;
    }

    public function remoteEnabled(): bool
    {
        return $this->get('remote_type') !== 'none' && $this->get('remote_host') !== '';
    }

    /** Konfiguracja dysku Laravel (Storage::build) dla serwera zdalnego. */
    public function remoteDiskConfig(): array
    {
        $type = $this->get('remote_type');

        $config = [
            'driver' => $type,
            'host' => $this->get('remote_host'),
            'username' => $this->get('remote_username'),
            'password' => $this->remotePassword(),
            'root' => $this->get('remote_root') ?: '/',
            'timeout' => 30,
            'throw' => true,
        ];

        if ($this->get('remote_port')) {
            $config['port'] = (int) $this->get('remote_port');
        }

        if ($type === 'ftp') {
            $config['ssl'] = (bool) $this->get('remote_ssl');
            $config['passive'] = true;
        }

        return $config;
    }
}
