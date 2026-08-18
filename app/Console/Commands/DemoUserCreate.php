<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUserCreate extends Command
{
    protected $signature = 'demo:user
                            {--dev : Skrót developerski: admin@dev.local / password, rola admin, --force}
                            {--email=demo@wecms.local : Adres e-mail użytkownika}
                            {--name=Demo Admin : Imię i nazwisko}
                            {--role=admin : Rola (admin, content_editor, coordinator, pr_editor, editor)}
                            {--password= : Hasło (domyślnie losowe 12-znakowe)}
                            {--force : Nadpisz istniejącego użytkownika z tym e-mailem}';

    protected $description = 'Tworzy demonstracyjnego użytkownika do testów i demo (tylko poza produkcją)';

    const DEV_EMAIL    = 'admin@dev.local';
    const DEV_PASSWORD = 'password';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Komenda niedostępna w środowisku produkcyjnym (APP_ENV=production).');
            return self::FAILURE;
        }

        $dev = $this->option('dev');

        $email    = $dev ? self::DEV_EMAIL    : $this->option('email');
        $name     = $dev ? 'Developer Admin'  : $this->option('name');
        $role     = $dev ? 'admin'            : $this->option('role');
        $password = $dev ? self::DEV_PASSWORD : ($this->option('password') ?: Str::password(12, symbols: false));

        $force = $dev || $this->option('force');

        if (! array_key_exists($role, User::ROLES)) {
            $this->error("Nieznana rola: {$role}");
            $this->line('Dostępne: ' . implode(', ', array_keys(User::ROLES)));
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && ! $force) {
            $this->warn("Użytkownik {$email} już istnieje. Użyj --force aby nadpisać.");
            return self::FAILURE;
        }

        if ($existing) {
            $existing->update([
                'name'     => $name,
                'password' => Hash::make($password),
                'role'     => $role,
            ]);
            $user = $existing;
            $action = 'Zaktualizowano';
        } else {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => $role,
            ]);
            $action = 'Utworzono';
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>' . $action . '</>', $user->name);
        $this->components->twoColumnDetail('E-mail',  $user->email);
        $this->components->twoColumnDetail('Hasło',   $password);
        $this->components->twoColumnDetail('Rola',    User::ROLES[$role]);
        $this->newLine();
        $this->components->warn('Zapisz hasło — nie będzie pokazane ponownie.');

        return self::SUCCESS;
    }
}
