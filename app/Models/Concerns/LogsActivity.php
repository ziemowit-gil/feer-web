<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/**
 * Zapisuje w dzienniku zdarzeń utworzenie/edycję/usunięcie modelu wraz z
 * autorem (zalogowanym użytkownikiem). Doklejany do modeli treści i kont.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));
    }

    public function recordActivity(string $event): void
    {
        $user = auth()->user();

        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?: $user?->email ?: 'system',
            'event' => $event,
            'subject_type' => class_basename($this),
            'subject_id' => $this->getKey(),
            'subject_label' => $this->activityLabel(),
        ]);
    }

    public function activityLabel(): string
    {
        return (string) ($this->title ?? $this->name ?? ('#'.$this->getKey()));
    }
}
