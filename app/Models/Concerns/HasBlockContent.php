<?php

namespace App\Models\Concerns;

use App\Models\SiteSetting;

/** Treść składana w edytorze blokowym (kolumna `blocks`) — wtedy formularz klasyczny nie zmienia `content`. */
trait HasBlockContent
{
    /** Czy treść jest w blokach (i edytor blokowy włączony) — zwykła edycja treści zablokowana. */
    public function hasLockedBlockContent(): bool
    {
        return $this->exists
            && is_array($this->blocks)
            && SiteSetting::current()->isModuleEnabled('block_editor');
    }
}
