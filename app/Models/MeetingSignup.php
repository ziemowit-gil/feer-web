<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSignup extends Model
{
    protected $fillable = ['name', 'email', 'term', 'message'];
}
