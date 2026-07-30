<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EtrContent extends Model
{
    protected $fillable = [
        'etrable_type', 'etrable_id',
        'is_enabled', 'etr_title', 'etr_summary', 'etr_content',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function etrable(): MorphTo
    {
        return $this->morphTo();
    }
}
