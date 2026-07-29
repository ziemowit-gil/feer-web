<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Konto współpracownika logującego się do stron wewnętrznych przez Microsoft
 * 365. Celowo oddzielone od modelu {@see User} (panel) — inny guard, inna
 * tabela, brak hasła (dostęp wyłącznie przez SSO).
 */
#[Fillable(['name', 'email', 'microsoft_id', 'avatar', 'last_login_at'])]
#[Hidden(['remember_token'])]
class Member extends Authenticatable
{
    protected $table = 'members';

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }
}
