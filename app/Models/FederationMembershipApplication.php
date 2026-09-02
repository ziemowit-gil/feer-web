<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Zgłoszenie przystąpienia do federacji (szablon "federation"), wraz ze
 * skanami dokumentów (deklaracja, uchwała, statut) wgranymi przez zgłaszającego.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FederationMembershipApplication extends Model
{
    use InteractsWithMedia;

    protected $fillable = [
        'organization_name', 'contact_name', 'email', 'phone', 'message', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
