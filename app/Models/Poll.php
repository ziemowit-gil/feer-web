<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = ['question', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('order');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->with('options')->latest()->first();
    }

    public function totalVotes(): int
    {
        return $this->options->sum('votes');
    }
}
