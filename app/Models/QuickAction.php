<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickAction extends Model
{
    protected $fillable = ['label', 'icon', 'url', 'order', 'color', 'is_negative', 'wide'];

    protected $casts = ['is_negative' => 'boolean', 'wide' => 'boolean'];
}
