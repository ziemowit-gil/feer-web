<?php

namespace App\Models\Concerns;

use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Wersjonowanie treści: przy każdej istotnej zmianie zapisuje migawkę
 * wybranych pól, pozwala przeglądać historię i przywrócić starszą wersję.
 *
 * Model musi zaimplementować `revisionFields(): array` (pola porównywane w historii). Opcjonalne
 * `revisionExtraFields(): array` to pola zapisywane w migawce i przywracane razem z nią, ale bez
 * porównania w widoku historii (np. drzewo bloków edytora blokowego obok wyrenderowanej treści).
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
            $changed = collect($model->revisionSnapshotFields())
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

    /** Pola zapisywane w migawce: porównywane + dodatkowe. @return array<int, string> */
    public function revisionSnapshotFields(): array
    {
        $extra = method_exists($this, 'revisionExtraFields') ? $this->revisionExtraFields() : [];

        return array_values(array_unique(array_merge($this->revisionFields(), $extra)));
    }

    /** Zapisz bieżący stan pól jako nową wersję i przytnij nadmiar. */
    public function recordRevision(): void
    {
        $data = collect($this->revisionSnapshotFields())
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
            ->only($this->revisionSnapshotFields())
            ->all();

        // Wersja z edytora blokowego ma bloki: przywracamy je razem z treścią, więc zapis nie może ich wyzerować
        // (HasBlockContent zeruje bloki, gdy `content` zmienia się poza edytorem). Starsze wersje — bez bloków —
        // przywracają samą treść jak dotąd.
        if (array_key_exists('blocks', $data) && property_exists($this, 'contentRenderedFromBlocks')) {
            $this->contentRenderedFromBlocks = true;
            try {
                $this->fill($data)->save();
            } finally {
                $this->contentRenderedFromBlocks = false;
            }

            return;
        }

        $this->fill($data)->save();
    }

    /** Lista pól objętych wersjonowaniem — nadpisywana w modelu. */
    abstract public function revisionFields(): array;
}
