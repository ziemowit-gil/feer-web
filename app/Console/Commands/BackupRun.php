<?php

namespace App\Console\Commands;

use App\Support\Backup\BackupManager;
use App\Support\Backup\BackupSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Tworzy kopię zapasową (bazy + pliki), usuwa stare ponad limit i wysyła na serwer zdalny.
 * Z harmonogramu wywoływana jako `backup:run --auto` (patrz routes/console.php).
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run
        {--auto : Kopia automatyczna (z harmonogramu)}
        {--no-files : Tylko bazy danych, bez plików}
        {--no-upload : Nie wysyłaj na serwer zdalny}';

    protected $description = 'Tworzy kopię zapasową bazy i plików (storage/app)';

    public const LAST_RESULT_KEY = 'backup:last_result';

    public function handle(): int
    {
        $settings = BackupSettings::load();
        $manager = new BackupManager($settings);

        try {
            $name = $manager->create(
                $this->option('auto') ? 'auto' : 'manual',
                $this->option('no-files') ? false : null,
            );
            $this->info("Utworzono: {$name}");

            $removed = $manager->prune();
            if ($removed) {
                $this->line("Usunięto starych kopii: {$removed}");
            }

            if (! $this->option('no-upload') && $settings->remoteEnabled()) {
                $manager->upload($name);
                $this->info('Wysłano na serwer zdalny ('.$settings->get('remote_host').').');
            }

            Cache::forever(self::LAST_RESULT_KEY, ['ok' => true, 'at' => now()->toIso8601String(), 'message' => $name]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Cache::forever(self::LAST_RESULT_KEY, ['ok' => false, 'at' => now()->toIso8601String(), 'message' => $e->getMessage()]);
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
