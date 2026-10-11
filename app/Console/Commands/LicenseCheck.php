<?php

namespace App\Console\Commands;

use App\Support\Edition;
use App\Support\LicenseOnlineCheck;
use Illuminate\Console\Command;

/** Sprawdza klucz licencji w panelu licencji weCMS — patrz App\Support\LicenseOnlineCheck. */
class LicenseCheck extends Command
{
    protected $signature = 'license:check';

    protected $description = 'Sprawdź klucz licencji w panelu licencji weCMS (unieważnienie, zastąpienie)';

    public function handle(): int
    {
        if (! LicenseOnlineCheck::enabled()) {
            $this->line('Sprawdzanie online wyłączone (brak LICENSE_KEY albo LICENSE_CHECK_URL).');

            return self::SUCCESS;
        }

        $result = LicenseOnlineCheck::run();

        if ($result === null) {
            $this->warn('Panel licencji nie odpowiedział — zostaje poprzedni wynik.');

            return self::FAILURE;
        }

        $this->line($result['valid']
            ? 'Panel licencji: klucz ważny ('.$result['edition'].').'
            : 'Panel licencji: '.(LicenseOnlineCheck::REASON_LABELS[$result['reason']] ?? $result['reason']).'.');
        $this->line('Edycja: '.(Edition::isPro() ? 'płatna' : 'darmowa').' — '.Edition::reason());

        return self::SUCCESS;
    }
}
