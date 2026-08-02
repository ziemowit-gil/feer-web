<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'                => 'Administrator Demo',
                'email'               => 'admin@demo.feer.org.pl',
                'role'                => User::ROLE_ADMIN,
            ],
            [
                'name'                => 'Redaktor Demo',
                'email'               => 'redaktor@demo.feer.org.pl',
                'role'                => User::ROLE_EDITOR,
            ],
            [
                'name'                => 'Edytor BIP Demo',
                'email'               => 'bip@demo.feer.org.pl',
                'role'                => User::ROLE_BIP_EDITOR,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password'            => Hash::make('demo12(@'),
                    'email_verified_at'   => now(),
                    'local_login_allowed' => true,
                ]),
            );
        }
    }
}
