<?php

namespace App\Support;

/**
 * Położenie pliku SQLite bloga „Wiem FEER” (połączenie `blog`).
 *
 * Historycznie baza leżała w database/blog.sqlite, czyli w ścieżce śledzonej
 * przez gita — na produkcji zawiera żywe dane. Docelowo leży w
 * storage/app/blog/blog.sqlite (storage/ nie jest nadpisywany przy wdrożeniu).
 *
 * Kolejność wyboru (patrz config/database.php):
 *   1. BLOG_DB_DATABASE z .env, jeśli niepusta,
 *   2. nowa lokalizacja, jeśli plik już istnieje (po `blog:przenies-baze`),
 *   3. stara lokalizacja — dopóki kopia nie powstanie, nic się nie zmienia.
 *
 * Szczegóły migracji: docs/blog-db-migration.md.
 */
final class BlogDatabasePath
{
    /** Względem storage_path(). */
    public const NEW_RELATIVE = 'app/blog/blog.sqlite';

    /** Względem database_path(). */
    public const LEGACY_RELATIVE = 'blog.sqlite';

    public static function resolve(?string $env, string $newPath, string $legacyPath): string
    {
        if ($env !== null && trim($env) !== '') {
            return $env;
        }

        if (is_file($newPath)) {
            return $newPath;
        }

        return $legacyPath;
    }

    public static function newPath(): string
    {
        return storage_path(self::NEW_RELATIVE);
    }

    public static function legacyPath(): string
    {
        return database_path(self::LEGACY_RELATIVE);
    }

    public static function backupDir(): string
    {
        return storage_path('app/blog/backups');
    }
}
