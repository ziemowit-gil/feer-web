<?php

declare(strict_types=1);

namespace Modules\Contact\Support;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Przenosi dawne strony „Książka telefoniczna” (typ `phone_book`, osoby w polach strony: type_data.contacts) na typ
 * „Kontakt — książka” (`contact_book`, osoby w tabeli phone_book_entries wspólnej z modułem „Książka telefoniczna”).
 *
 *  - osoby ze strony trafiają do tabeli witryny, do której należy strona (bez duplikatów: ten sam dział, imię i nazwisko
 *    oraz numer to ta sama osoba), po osobach, które już tam są;
 *  - strona dostaje typ `contact_book`; jej ustawienia (układ, kolejność, ukrywanie numerów) zostają, a stare pole
 *    `type_data.contacts` zostaje w stronie (nie jest już używane, ale pozwala wrócić do poprzedniego typu);
 *  - przed zmianą zapisywana jest kopia (id, witryna, adres, stary typ i pełne `type_data` każdej przenoszonej strony)
 *    w storage/app/module-backups/contact-<data>.json — z niej korzysta cofnięcie (restore()).
 *
 * Idempotentne: po przeniesieniu nie ma już stron starego typu, więc kolejne uruchomienie niczego nie zmienia.
 */
final class LegacyPagesConverter
{
    public const OLD_TYPE = 'phone_book';

    public const NEW_TYPE = 'contact_book';

    public const BACKUP_DIR = 'app/module-backups';

    /**
     * @return array{pages: int, created: int, existing: int, backup: ?string, dry_run: bool, note: ?string}
     */
    public function convert(bool $dryRun = false): array
    {
        $result = ['pages' => 0, 'created' => 0, 'existing' => 0, 'backup' => null, 'dry_run' => $dryRun, 'note' => null];

        if (! Schema::hasTable('pages') || ! Schema::hasTable('phone_book_entries')) {
            $result['note'] = 'Brak tabeli stron albo książki telefonicznej — nic nie przeniesiono (uruchom migracje).';

            return $result;
        }

        $pages = Page::withoutGlobalScopes()->withTrashed()->where('type', self::OLD_TYPE)->orderBy('id')->get();
        if ($pages->isEmpty()) {
            return $result;
        }

        $backup = [];
        DB::transaction(function () use ($pages, $dryRun, &$result, &$backup): void {
            foreach ($pages as $page) {
                $data = is_array($page->type_data) ? $page->type_data : [];
                $backup[] = [
                    'id' => $page->id, 'site_id' => $page->site_id, 'slug' => $page->slug, 'title' => $page->title,
                    'old_type' => $page->type, 'type_data' => $page->getRawOriginal('type_data'),
                ];
                $result['pages']++;

                [$created, $existing] = $this->moveContacts($page->site_id, array_values(array_filter($data['contacts'] ?? [], 'is_array')), $dryRun);
                $result['created'] += $created;
                $result['existing'] += $existing;

                if (! $dryRun) {
                    // Zapis bez zdarzeń modelu (hooki treści, historia), tylko kolumna typu.
                    DB::table('pages')->where('id', $page->id)->update(['type' => self::NEW_TYPE]);
                }
            }
        });

        if (! $dryRun) {
            $result['backup'] = $this->writeBackup($backup);
        }

        return $result;
    }

    /**
     * Cofa przeniesienie: strony z kopii wracają do typu `phone_book` (osoby dodane do tabeli zostają). Bez podanej
     * ścieżki bierze najnowszą kopię. Zwraca liczbę przywróconych stron.
     */
    public function restore(?string $backupPath = null): int
    {
        $backupPath ??= $this->latestBackup();
        if ($backupPath === null || ! is_file($backupPath) || ! Schema::hasTable('pages')) {
            return 0;
        }

        $rows = json_decode((string) file_get_contents($backupPath), true)['pages'] ?? [];
        $restored = 0;
        foreach ($rows as $row) {
            $restored += DB::table('pages')->where('id', $row['id'])->where('type', self::NEW_TYPE)->update(['type' => $row['old_type'] ?? self::OLD_TYPE]);
        }

        return $restored;
    }

    public function latestBackup(): ?string
    {
        $files = glob(storage_path(self::BACKUP_DIR).'/contact-*.json') ?: [];
        sort($files);

        return $files === [] ? null : end($files);
    }

    /**
     * Dodaje osoby do tabeli witryny bez duplikatów. @param array<int, array<string, mixed>> $rows
     *
     * @return array{0: int, 1: int} [dodane, już były]
     */
    private function moveContacts(?int $siteId, array $rows, bool $dryRun): array
    {
        $created = $existing = 0;
        $key = fn (array $r): string => mb_strtolower(trim((string) ($r['group'] ?? '')).'|'.trim((string) ($r['name'] ?? '')).'|'.preg_replace('/\D+/', '', (string) ($r['phone'] ?? '')));

        $known = [];
        foreach (PhoneBookEntry::query()->where('site_id', $siteId)->get(['group', 'name', 'phone']) as $e) {
            $known[$key($e->only(['group', 'name', 'phone']))] = true;
        }
        $order = (int) PhoneBookEntry::query()->where('site_id', $siteId)->max('order');

        foreach ($rows as $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }
            if (isset($known[$key($row)])) {
                $existing++;

                continue;
            }
            $known[$key($row)] = true;
            $created++;
            if ($dryRun) {
                continue;
            }
            PhoneBookEntry::create([
                'site_id' => $siteId,
                'group' => trim((string) ($row['group'] ?? '')) ?: null,
                'name' => trim((string) $row['name']),
                'role' => trim((string) ($row['role'] ?? '')) ?: null,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'topic' => trim((string) ($row['topic'] ?? '')) ?: null,
                'hours' => trim((string) ($row['hours'] ?? '')) ?: null,
                'is_active' => true,
                'order' => ++$order,
            ]);
        }

        return [$created, $existing];
    }

    /** @param array<int, array<string, mixed>> $pages */
    private function writeBackup(array $pages): string
    {
        $dir = storage_path(self::BACKUP_DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/contact-'.date('Ymd-His').'.json';
        file_put_contents($path, json_encode(
            ['created_at' => now()->toIso8601String(), 'note' => 'Strony „Książka telefoniczna” przeniesione na typ „Kontakt — książka” (moduł Kontakt).', 'pages' => $pages],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return $path;
    }
}
