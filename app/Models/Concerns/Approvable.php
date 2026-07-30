<?php

namespace App\Models\Concerns;

use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ContentSubmittedForApproval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

/**
 * Treść objęta obiegiem akceptacji: może „oczekiwać na zatwierdzenie"
 * (pending_approval) i zna autora zgłoszenia (submitted_by_id).
 *
 * Gdy treść trafia do kolejki „do zatwierdzenia", powiadamia mailowo
 * osoby uprawnione do akceptacji.
 */
trait Approvable
{
    public static function bootApprovable(): void
    {
        static::saved(function (Model $model) {
            $justSubmitted = ($model->wasRecentlyCreated && $model->pending_approval)
                || ($model->wasChanged('pending_approval') && $model->pending_approval);

            if (! $justSubmitted) {
                return;
            }

            $approvers = User::approvers()
                ->whereKeyNot($model->submitted_by_id ?? 0)
                ->whereNotNull('email')
                ->get();

            if ($approvers->isNotEmpty()) {
                Notification::send(
                    $approvers,
                    new ContentSubmittedForApproval($model, $model->approvalLabel(), $model->submittedBy?->name),
                );
            }
        });
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('pending_approval', true);
    }

    /** Ludzka etykieta typu treści (do maili i list akceptacji). */
    public function approvalLabel(): string
    {
        return match (static::class) {
            News::class => 'Aktualność',
            Page::class => 'Strona',
            Project::class => 'Projekt',
            default => 'Treść',
        };
    }
}
