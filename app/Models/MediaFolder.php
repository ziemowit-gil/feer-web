<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaFolder extends Model
{
    protected $fillable = ['name', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id')->orderBy('name');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'media_folder_id');
    }

    /**
     * Ancestor chain from the root folder down to this one, for breadcrumbs.
     */
    public function path(): array
    {
        $path = [];
        $folder = $this;

        while ($folder) {
            array_unshift($path, $folder);
            $folder = $folder->parent;
        }

        return $path;
    }

    public function fullPath(): string
    {
        return collect($this->path())->pluck('name')->implode(' / ');
    }
}
