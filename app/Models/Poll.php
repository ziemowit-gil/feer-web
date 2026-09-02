<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use \App\Models\Concerns\BelongsToSite;
    use \App\Models\Concerns\LogsActivity;

    protected $fillable = ['site_id', 'question', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('order');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->forCurrentSite()->with('options')->latest()->first();
    }

    public function totalVotes(): int
    {
        return $this->options->sum('votes');
    }
}
