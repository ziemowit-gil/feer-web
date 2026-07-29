<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Treść objęta obiegiem akceptacji: może „oczekiwać na zatwierdzenie"
 * (pending_approval) i zna autora zgłoszenia (submitted_by_id).
 */
trait Approvable
{
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('pending_approval', true);
    }
}
