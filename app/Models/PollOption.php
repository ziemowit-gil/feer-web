<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollOption extends Model
{
    protected $fillable = ['poll_id', 'label', 'votes', 'order'];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function percent(int $totalVotes): int
    {
        return $totalVotes > 0 ? (int) round($this->votes / $totalVotes * 100) : 0;
    }
}
