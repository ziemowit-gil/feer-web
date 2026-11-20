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
        $properties = ['label' => $this->activityLabel()];
        // Edycja: które pola i z czego na co (jak sys_log w TYPO3). Pomijamy znaczniki czasu i dane wrażliwe,
        // a długie treści (HTML, bloki) tylko odnotowujemy — pełne wersje są w historii zmian.
        if ($event === 'updated') {
            [$old, $new] = $this->activityChanges();
            if ($new === []) {
                return; // zapis bez zmian w polach (np. sam znacznik czasu)
            }
            $properties += ['old' => $old, 'attributes' => $new];
        }

        activity('cms')
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties($properties)
            ->event($event)
            ->log(class_basename($this) . ' ' . $event);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} stare i nowe wartości zmienionych pól */
    protected function activityChanges(): array
    {
        $skip = ['updated_at', 'created_at', 'remember_token', 'notifications_seen_at', 'expiry_handled_at', 'last_used_at'];
        $old = [];
        $new = [];
        foreach (array_keys($this->getChanges()) as $field) {
            if (in_array($field, $skip, true)) {
                continue;
            }
            // Hasła, sekrety i kody 2FA: tylko informacja, że się zmieniły — bez wartości.
            if (preg_match('/secret|token|password|recovery|yubikey/i', $field)) {
                [$old[$field], $new[$field]] = ['[ukryte]', '[zmieniono]'];

                continue;
            }
            $old[$field] = self::activityValue($field, $this->getOriginal($field));
            $new[$field] = self::activityValue($field, $this->getAttribute($field));
        }

        return [$old, $new];
    }

    private static function activityValue(string $field, mixed $value): mixed
    {
        if (in_array($field, ['content', 'blocks', 'data', 'layout_data', 'type_data', 'cooperation_data'], true)) {
            return $value === null || $value === '' || $value === [] ? null : '[treść]';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return is_string($value) ? mb_strimwidth($value, 0, 300, '…') : $value;
    }

    public function activityLabel(): string
    {
        return (string) ($this->title ?? $this->name ?? ('#' . $this->getKey()));
    }
}
