<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFaq extends Model
{
    protected $fillable = ['event_id', 'question', 'answer', 'order'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
