<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;

class Facilitator extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name', 'role', 'bio',
        'website', 'linkedin', 'facebook', 'instagram', 'twitter',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function photoUrl(): ?string
    {
        return $this->getFirstMediaUrl('photo') ?: null;
    }

    /** Tablica danych do auto-wypełnienia formularza wydarzenia (dla Alpine.js). */
    public function toPickerArray(): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'role'      => $this->role ?? '',
            'bio'       => $this->bio ?? '',
            'website'   => $this->website ?? '',
            'linkedin'  => $this->linkedin ?? '',
            'facebook'  => $this->facebook ?? '',
            'instagram' => $this->instagram ?? '',
            'twitter'   => $this->twitter ?? '',
            'photo'     => $this->photoUrl() ?? '',
        ];
    }
}
