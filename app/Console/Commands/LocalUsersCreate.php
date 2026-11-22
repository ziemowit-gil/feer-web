<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Konta robocze admin@local (Administrator) i edytor@local (Edytor treści).
 * Domena @local nie wymaga 2FA (User::isDemoAccount), a local_login_allowed
 * pozwala logować się hasłem także w trybie „tylko SSO / tylko Microsoft”.
 * Komenda jest powtarzalna: istniejące konto dostaje z powrotem rolę, hasło
 * i dostęp (bez dat ważności), nazwa zostaje.
 */
class LocalUsersCreate extends Command
{
    use ConfirmableTrait;

    protected $signature = 'users:lokalne
                            {--haslo=123456 : Hasło obu kont}
                            {--force : Nie pytaj w środowisku produkcyjnym}';

    protected $description = 'Tworzy lub odświeża konta admin@local i edytor@local (bez 2FA, logowanie hasłem)';

    /** e-mail => [nazwa, rola] */
    public const ACCOUNTS = [
        'admin@local'  => ['Administrator (konto lokalne)', User::ROLE_ADMIN],
        'edytor@local' => ['Edytor (konto lokalne)', User::ROLE_CONTENT_EDITOR],
    ];

    public function handle(): int
    {
        if (! $this->confirmToProceed('Konta @local mają proste hasło i nie wymagają 2FA.')) {
            return self::FAILURE;
        }

        $password = (string) $this->option('haslo');
        if ($password === '') {
            $this->error('Hasło nie może być puste.');

            return self::FAILURE;
        }

        $rows = [];
        foreach (self::ACCOUNTS as $email => [$name, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $created = ! $user->exists;

            if ($created) {
                $user->name = $name;
            }
            $user->fill([
                'password'            => $password,
                'role'                => $role,
                'local_login_allowed' => true,
                'active_from'         => null,
                'active_until'        => null,
            ]);
            $user->save();

            $rows[] = [$created ? 'utworzono' : 'zaktualizowano', $email, User::ROLES[$role]];
        }

        $this->table(['Stan', 'E-mail', 'Rola'], $rows);
        $this->line('Hasło obu kont: '.$password);

        return self::SUCCESS;
    }
}
