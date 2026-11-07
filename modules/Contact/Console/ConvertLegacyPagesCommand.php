<?php

declare(strict_types=1);

namespace Modules\Contact\Console;

use Illuminate\Console\Command;
use Modules\Contact\Support\LegacyPagesConverter;

/**
 * kontakt:przenies-strony — przenosi dawne strony „Książka telefoniczna” na typ „Kontakt — książka” (osoby do tabeli
 * książki telefonicznej, kopia stron w storage/app/module-backups). --sprawdz pokazuje, co by się stało; --cofnij wraca
 * do poprzedniego typu stron z najnowszej kopii.
 */
final class ConvertLegacyPagesCommand extends Command
{
    protected $signature = 'kontakt:przenies-strony {--sprawdz : Tylko pokaż, co zostanie przeniesione, bez zmian} {--cofnij : Przywróć dawny typ stron z najnowszej kopii}';

    protected $description = 'Przenosi strony „Książka telefoniczna” na typ „Kontakt — książka” (moduł Kontakt)';

    public function handle(LegacyPagesConverter $converter): int
    {
        if ($this->option('cofnij')) {
            $n = $converter->restore();
            $this->info($n > 0 ? "Przywrócono dawny typ stron: {$n}." : 'Brak kopii albo stron do przywrócenia.');

            return self::SUCCESS;
        }

        $r = $converter->convert((bool) $this->option('sprawdz'));
        if ($r['note']) {
            $this->warn($r['note']);

            return self::SUCCESS;
        }

        $prefix = $r['dry_run'] ? '[próba] ' : '';
        $this->info("{$prefix}Strony do przeniesienia: {$r['pages']}; osoby dodane do książki: {$r['created']}; już były: {$r['existing']}.");
        if ($r['backup']) {
            $this->line('Kopia stron: '.$r['backup']);
        }

        return self::SUCCESS;
    }
}
