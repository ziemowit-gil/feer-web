<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessibilityReport extends Model
{
    protected $fillable = ['name', 'email', 'page_url', 'message'];
}
