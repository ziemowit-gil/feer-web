<?php

namespace App\Models\Concerns;

/**
 * Zapisuje w dzienniku zdarzeń spatie/laravel-activitylog zdarzenia
 * CRUD modelu wraz z autorem i migawką tytułu (właściwość „label").
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
        activity('cms')
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperty('label', $this->activityLabel())
            ->event($event)
            ->log(class_basename($this) . ' ' . $event);
    }

    public function activityLabel(): string
    {
        return (string) ($this->title ?? $this->name ?? ('#' . $this->getKey()));
    }
}
