<?php

namespace App\Support;

use PDO;
use RuntimeException;

/**
 * Bezpieczne skopiowanie bazy bloga do nowej lokalizacji.
 *
 * Źródło nigdy nie jest usuwane ani modyfikowane (poza checkpointem WAL).
 * Kolejność: kontrola integralności źródła → kopia zapasowa → kopia do pliku
 * tymczasowego obok celu (VACUUM INTO, a gdy niedostępne — copy) → porównanie
 * liczby wierszy w każdej tabeli + integrity_check → atomowe rename na cel.
 * Przy jakiejkolwiek rozbieżności cel nie powstaje, więc aplikacja dalej
 * używa starej lokalizacji (BlogDatabasePath::resolve()).
 */
final class BlogDatabaseMover
{
    /**
     * @return array{method: string, backup: string, tables: array<string, int>}
     */
    public function move(string $source, string $target, string $backupDir): array
    {
        if (! is_file($source)) {
            throw new RuntimeException("Brak pliku źródłowego: {$source}");
        }
        if (file_exists($target)) {
            throw new RuntimeException("Plik docelowy już istnieje: {$target}");
        }

        $src = $this->open($source);
        $this->assertIntegrity($src, $source);

        // Wszystko z dziennika WAL trafia do głównego pliku, żeby zwykła kopia
        // (kopia zapasowa, fallback) była kompletna.
        $src->query('PRAGMA wal_checkpoint(TRUNCATE)');

        $this->ensureDir(dirname($target));
        $this->ensureDir($backupDir);

        $backup = rtrim($backupDir, '/').'/blog-'.date('Ymd-His').'.sqlite';
        if (! @copy($source, $backup)) {
            throw new RuntimeException("Nie udało się utworzyć kopii zapasowej: {$backup}");
        }

        $tmp = $target.'.tmp-'.bin2hex(random_bytes(4));
        $method = 'VACUUM INTO';

        try {
            try {
                $src->prepare('VACUUM INTO ?')->execute([$tmp]);
            } catch (\Throwable) {
                @unlink($tmp);
                $method = 'copy';
                if (! @copy($source, $tmp)) {
                    throw new RuntimeException("Nie udało się skopiować bazy do {$tmp}");
                }
            }

            $copy = $this->open($tmp);
            $this->assertIntegrity($copy, $tmp);

            $expected = $this->rowCounts($src);
            $actual = $this->rowCounts($copy);
            $copy = null;

            if ($expected !== $actual) {
                throw new RuntimeException('Liczba wierszy w kopii różni się od źródła: '
                    .json_encode(['zrodlo' => $expected, 'kopia' => $actual], JSON_UNESCAPED_UNICODE));
            }

            $perms = @fileperms($source);
            if ($perms !== false) {
                @chmod($tmp, $perms & 0777);
            }

            if (! @rename($tmp, $target)) {
                throw new RuntimeException("Nie udało się przenieść {$tmp} → {$target}");
            }
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }

        return ['method' => $method, 'backup' => $backup, 'tables' => $expected];
    }

    /**
     * Liczba wierszy w każdej tabeli użytkownika (bez tabel wewnętrznych SQLite).
     *
     * @return array<string, int>
     */
    public function rowCounts(PDO $pdo): array
    {
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(PDO::FETCH_COLUMN);

        $counts = [];
        foreach ($tables as $table) {
            $quoted = '"'.str_replace('"', '""', $table).'"';
            $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$quoted}")->fetchColumn();
        }

        return $counts;
    }

    public function open(string $path): PDO
    {
        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    private function assertIntegrity(PDO $pdo, string $path): void
    {
        $result = $pdo->query('PRAGMA integrity_check')->fetchColumn();
        if ($result !== 'ok') {
            throw new RuntimeException("Kontrola integralności nie przeszła ({$path}): {$result}");
        }
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Nie udało się utworzyć katalogu: {$dir}");
        }
    }
}
