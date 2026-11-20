<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Blok treści zbudowany w kreatorze edytora (przyciski CTA albo akordeon); osadzany shortcodem [blok:ID].
 */
class ContentBlock extends Model
{
    use \App\Models\Concerns\BelongsToSite;
    // Historia wersji tylko dla bloków wspólnych (zmiana działa na wielu stronach, więc musi dać się cofnąć).
    use \App\Models\Concerns\HasRevisions { recordRevision as private recordRevisionNow; }

    public const TYPES = ['cta' => 'Przyciski CTA', 'accordion' => 'Akordeon', 'callout' => 'Ramka informacyjna', 'pricing' => 'Cennik'];

    public const CALLOUT_VARIANTS = ['blue' => 'Niebieska', 'gold' => 'Złota', 'red' => 'Czerwona', 'green' => 'Zielona'];

    public const CALLOUT_ICONS = ['exclamation' => 'fa-exclamation', 'info' => 'fa-info', 'coins' => 'fa-coins', 'warning' => 'fa-triangle-exclamation', 'check' => 'fa-check'];

    /**
     * Blok wspólny z edytora blokowego (jak „Wstaw rekord” w TYPO3): data.blocks to bloki edytora, osadzane na wielu
     * stronach shortcodem [blok:ID] i renderowane przy wyświetlaniu — zmiana w jednym miejscu działa wszędzie.
     * Poza TYPES, żeby kreator klasycznego edytora go nie pokazywał ani nie nadpisywał.
     */
    public const SHARED = 'blocks';

    protected $fillable = ['site_id', 'type', 'name', 'data', 'created_by'];

    public function isShared(): bool
    {
        return $this->type === self::SHARED;
    }

    /** Strony bieżącej witryny, w których treści jest ten blok ([blok:ID]). */
    public function usedOnPages(): \Illuminate\Support\Collection
    {
        return Page::forCurrentSite()->where('content', 'like', '%[blok:'.$this->id.']%')->orderBy('title')->get(['id', 'title']);
    }

    protected $casts = ['data' => 'array'];

    // ── Blok wspólny w edytorze blokowym i w historii wersji ────────────────────
    // Edytor blokowy (cel `shared`) i historia czytają rekord jak stronę: tytuł, bloki, treść HTML.

    public function getTitleAttribute(): string
    {
        return (string) $this->name;
    }

    /** Bloki edytora bloku wspólnego (null dla bloków z kreatora). */
    public function getBlocksAttribute(): ?array
    {
        return $this->isShared() ? array_values((array) ($this->data['blocks'] ?? [])) : null;
    }

    /** Treść bloku wspólnego jako HTML — bez kolumny `content`, liczona z bloków (podgląd w historii). */
    public function getContentAttribute(): string
    {
        return $this->isShared() ? \App\Support\BlockRenderer::render((array) ($this->data['blocks'] ?? [])) : '';
    }

    public function getIsPublishedAttribute(): bool
    {
        return true;
    }

    public function getMetaDescriptionAttribute(): string
    {
        return '';
    }

    public function revisionFields(): array
    {
        return ['name', 'content'];
    }

    /** Bloki (`data`) zapisywane w każdej wersji — przywrócenie wraca do nich, a nie do HTML. */
    public function revisionExtraFields(): array
    {
        return ['data'];
    }

    public function revisionFieldLabels(): array
    {
        return ['name' => 'Nazwa bloku wspólnego', 'content' => 'Treść'];
    }

    public function recordRevision(): void
    {
        if ($this->isShared()) {
            $this->recordRevisionNow();
        }
    }

    /** Przywraca nazwę i bloki z wersji (treść HTML liczy się z bloków). */
    public function restoreRevision(ContentRevision $revision): void
    {
        $this->fill(array_intersect_key((array) $revision->data, array_flip(['name', 'data'])))->save();
    }
}
