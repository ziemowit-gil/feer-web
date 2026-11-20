<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wygaszanie kont po terminie ważności (users.active_until, jak „stop” w TYPO3): kończy ich zapisane sesje,
 * usuwa „zapamiętaj mnie” i odnotowuje wygaszenie w dzienniku logowań. Logowanie i tak blokuje LoginRequest,
 * a otwartą sesję GuardAccountSession — cron sprząta resztę i zostawia ślad. Uruchamiany co godzinę.
 */
class UsersExpireCommand extends Command
{
    protected $signature = 'users:wygas {--dry-run : Tylko pokaż konta do wygaszenia}';

    protected $description = 'Wygasza konta po terminie ważności: kończy sesje, usuwa „zapamiętaj mnie”, zapisuje w dzienniku.';

    public function handle(): int
    {
        if (! Schema::hasColumn('users', 'active_until')) {
            $this->warn('Brak kolumny users.active_until — uruchom migracje.');

            return self::SUCCESS;
        }

        $users = User::query()->whereNotNull('active_until')->where('active_until', '<=', now())->whereNull('expiry_handled_at')->get();
        $sessions = config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'));

        foreach ($users as $user) {
            $this->line(($this->option('dry-run') ? '[podgląd] ' : '').$user->email.' — wygasło '.$user->active_until->timezone('Europe/Warsaw')->format('Y-m-d H:i'));
            if ($this->option('dry-run')) {
                continue;
            }
            if ($sessions) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            $user->forceFill(['remember_token' => null, 'expiry_handled_at' => now()])->saveQuietly();
            activity('auth')->performedOn($user)->event('account_expired')->withProperty('label', $user->name)->log('Wygaszenie konta: '.$user->name);
        }

        $this->info($users->isEmpty() ? 'Brak kont do wygaszenia.' : 'Kont: '.$users->count().'.');

        return self::SUCCESS;
    }
}
