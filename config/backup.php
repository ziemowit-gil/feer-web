<?php

/**
 * Kopie zapasowe weCMS (patrz App\Support\Backup\BackupManager).
 *
 * Kopia to archiwum ZIP: manifest.json, zrzuty baz (db/) i pliki z storage/app (files/).
 * Harmonogram, retencję i serwer zdalny ustawia się w panelu (Narzędzia → Kopie zapasowe);
 * te ustawienia leżą w pliku obok kopii, więc przywrócenie bazy ich nie cofa.
 */
return [
    // Katalog na archiwa (poza public — pobieranie tylko przez panel).
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    // Połączenia bazodanowe w kopii. Nieistniejące / nieskonfigurowane są pomijane.
    'connections' => array_values(array_unique(array_filter([
        env('DB_CONNECTION', 'sqlite'),
        'blog',
    ]))),

    // Katalogi (względem storage/app) pomijane w kopii plików.
    'exclude' => ['backups', 'livewire-tmp', 'tmp', 'temp', 'browsershot'],

    // Rozszerzenia już skompresowane — zapisywane w ZIP bez kompresji (szybciej, ten sam rozmiar).
    'store_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'mp4', 'webm', 'mp3', 'm4a', 'ogg', 'zip', 'gz', 'pdf', 'docx', 'xlsx', 'pptx', 'odt', 'woff', 'woff2'],

    // Binarki do zrzutu/odtworzenia MySQL/MariaDB i PostgreSQL.
    'binaries' => [
        'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
        'mysql' => env('BACKUP_MYSQL', 'mysql'),
        'pg_dump' => env('BACKUP_PG_DUMP', 'pg_dump'),
        'psql' => env('BACKUP_PSQL', 'psql'),
    ],
];
