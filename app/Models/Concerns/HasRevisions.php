<?php

namespace App\Models\Concerns;

use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Wersjonowanie treści: przy każdej istotnej zmianie zapisuje migawkę
 * wybranych pól, pozwala przeglądać historię i przywrócić starszą wersję.
 *
 * Model musi zaimplementować `revisionFields(): array`.
 */
trait HasRevisions
{
    /** Ile wersji przechowywać na rekord (starsze są przycinane). */
    protected int $maxRevisions = 30;

    public static function bootHasRevisions(): void
    {
        // Wersja początkowa przy utworzeniu.
        static::created(fn (Model $model) => $model->recordRevision());

        // Kolejna wersja tylko gdy zmieniło się któreś z pól treści
        // (samo przełączenie publikacji czy innej flagi nie tworzy wersji).
        static::updated(function (Model $model) {
            $changed = collect($model->revisionFields())
                ->contains(fn ($f) => $model->wasChanged($f));

            if ($changed) {
                $model->recordRevision();
            }
        });
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisionable')->latest();
    }

    /** Zapisz bieżący stan pól jako nową wersję i przytnij nadmiar. */
    public function recordRevision(): void
    {
        $data = collect($this->revisionFields())
            ->mapWithKeys(fn ($field) => [$field => $this->getAttribute($field)])
            ->all();

        $this->revisions()->create([
            'user_id' => auth()->id(),
            'data' => $data,
        ]);

        $keep = $this->revisions()->take($this->maxRevisions)->pluck('id');
        $this->revisions()->whereNotIn('id', $keep)->delete();
    }

    /** Przywróć pola z danej wersji (tworzy przy tym nową wersję bieżącą). */
    public function restoreRevision(ContentRevision $revision): void
    {
        $data = collect($revision->data)
            ->only($this->revisionFields())
            ->all();

        $this->fill($data)->save();
    }

    /** Lista pól objętych wersjonowaniem — nadpisywana w modelu. */
    abstract public function revisionFields(): array;
}
