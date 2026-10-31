<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialSubscriber extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    protected $fillable = ['site_id', 'email'];
}
