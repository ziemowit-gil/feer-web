<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'description', 'status', 'priority',
        'assigned_to', 'created_by', 'due_date', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['todo', 'in_progress']);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && ! $this->isDone() && $this->due_date->isPast();
    }

    public function isDueSoon(): bool
    {
        return $this->due_date && ! $this->isDone()
            && $this->due_date->isToday() || ($this->due_date && $this->due_date->isTomorrow() && ! $this->isDone());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'todo' => 'Do zrobienia',
            'in_progress' => 'W trakcie',
            'done' => 'Zrobione',
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Niski',
            'normal' => 'Normalny',
            'high' => 'Wysoki',
        };
    }
}
