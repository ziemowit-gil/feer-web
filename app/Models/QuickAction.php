<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickAction extends Model
{
    protected $fillable = ['label', 'icon', 'url', 'order', 'color'];
}
