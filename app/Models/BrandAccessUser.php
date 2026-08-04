<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAccessUser extends Model
{
    protected $fillable = ['page_id', 'name', 'login', 'password', 'notes', 'is_active', 'last_login_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** Losuje login: 10 znaków a-z0-9 (bez mylących cyfr 0/O, 1/l). */
    public static function generateLogin(): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyz23456789';

        return substr(str_shuffle(str_repeat($chars, 3)), 0, 10);
    }

    /**
     * Losuje hasło: dokładnie 13 znaków z zestawu a-z, 0-9, !@#$%^&*.
     * Gwarantuje przynajmniej jedną cyfrę i jeden znak specjalny.
     */
    public static function generatePassword(): string
    {
        $letters  = 'abcdefghijklmnopqrstuvwxyz';
        $digits   = '0123456789';
        $specials = '!@#$%^&*';
        $all      = $letters . $digits . $specials;

        do {
            $pwd = '';
            for ($i = 0; $i < 13; $i++) {
                $pwd .= $all[random_int(0, strlen($all) - 1)];
            }
        } while (
            ! preg_match('/[0-9]/', $pwd) ||
            ! preg_match('/[!@#$%^&*]/', $pwd)
        );

        return $pwd;
    }
}
