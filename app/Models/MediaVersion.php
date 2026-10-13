<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Poprzednia wersja pliku z biblioteki — zapisana przy podmianie (App\Services\Media\MediaReplacer). */
class MediaVersion extends Model
{
    protected $fillable = ['media_id', 'file_name', 'mime_type', 'size', 'path', 'replaced_by_user_id', 'note'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replaced_by_user_id');
    }

    /** Pełna ścieżka pliku wersji (dysk „local”). */
    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->path);
    }

    public function humanSize(): string
    {
        return \Illuminate\Support\Number::fileSize($this->size, 1);
    }
}
