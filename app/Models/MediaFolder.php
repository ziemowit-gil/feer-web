<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Grupy użytkowników z dostępem do folderu. Brak grup = folder widzą wszyscy
     * z dostępem do biblioteki; ograniczenie dziedziczą podfoldery.
     */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'media_folder_user_group');
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

    /**
     * Id folderów dostępnych dla użytkownika albo null, gdy widzi wszystkie (administrator).
     * Folder jest dostępny, gdy na całej ścieżce od korzenia każdy folder z ograniczeniem
     * wpuszcza grupę użytkownika.
     *
     * @return array<int>|null
     */
    public static function accessibleIdsFor(?User $user): ?array
    {
        if ($user?->isAdmin()) {
            return null;
        }

        /** @var Collection<int, MediaFolder> $folders */
        $folders = static::query()->with('userGroups:id')->get()->keyBy('id');
        $groupId = $user?->user_group_id;

        $allowed = [];
        foreach ($folders as $folder) {
            $ok = true;
            $node = $folder;
            $guard = 0;
            while ($node && $guard++ < 50) {
                $groups = $node->userGroups->pluck('id');
                if ($groups->isNotEmpty() && ! $groups->contains($groupId)) {
                    $ok = false;
                    break;
                }
                $node = $node->parent_id ? $folders->get($node->parent_id) : null;
            }
            if ($ok) {
                $allowed[] = $folder->id;
            }
        }

        return $allowed;
    }

    /** Czy użytkownik ma dostęp do folderu (null = katalog główny, zawsze dostępny). */
    public static function userCanAccess(?User $user, ?int $folderId): bool
    {
        if ($folderId === null) {
            return true;
        }
        $ids = static::accessibleIdsFor($user);

        return $ids === null || in_array($folderId, $ids, true);
    }

    /** Czy folder ma własne ograniczenie dostępu (przypisane grupy). */
    public function isRestricted(): bool
    {
        return $this->userGroups->isNotEmpty();
    }
}
