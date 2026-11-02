<?php

namespace App\Models\Concerns;

use App\Models\SiteSetting;

/** Treść składana w edytorze blokowym (kolumna `blocks`) — wtedy formularz klasyczny nie zmienia `content`. */
trait HasBlockContent
{
    /**
     * Ustawiane na czas zapisu z edytora blokowego (updateFromBlockEditor): `content` jest wtedy wyrenderowany
     * z `blocks`, więc zapis ich nie unieważnia — także gdy bloki się nie zmieniły, a HTML tak (np. po zmianie renderera).
     */
    public bool $contentRenderedFromBlocks = false;

    /** Czy treść jest w blokach (i edytor blokowy włączony) — zwykła edycja treści zablokowana. */
    public function hasLockedBlockContent(): bool
    {
        return $this->exists
            && is_array($this->blocks)
            && SiteSetting::current()->isModuleEnabled('block_editor');
    }

    /**
     * Czy ten zapis zmienia `content` poza edytorem blokowym (formularz, edycja na żywo, przywrócenie wersji),
     * więc zapisane bloki są nieaktualne i trzeba je wyzerować.
     */
    protected function blocksAreStale(): bool
    {
        return $this->exists
            && ! $this->contentRenderedFromBlocks
            && $this->isDirty('content')
            && ! $this->isDirty('blocks')
            && $this->blocks !== null;
    }

    /** Zapis z edytora blokowego: `content` pochodzi z `blocks`, więc ich nie zerujemy. */
    public function updateFromBlockEditor(array $attributes): bool
    {
        $this->contentRenderedFromBlocks = true;

        try {
            return $this->update($attributes);
        } finally {
            $this->contentRenderedFromBlocks = false;
        }
    }
}
