<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSignup extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'term', 'message', 'confirmed_at'];

    protected $casts = ['confirmed_at' => 'datetime'];

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
