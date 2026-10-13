<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /** Typy dokumentów, których dostępność sprawdza App\Services\Media\DocumentA11yChecker. */
    public const CHECKABLE_DOCUMENTS = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    protected function casts(): array
    {
        // Łączone przez Eloquent z $casts modelu Spatie (manipulations, custom_properties…).
        return [
            'a11y_report' => 'array',
            'a11y_checked_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    /** Poprzednie wersje pliku (najnowsza pierwsza). */
    public function versions(): HasMany
    {
        return $this->hasMany(MediaVersion::class)->latest('id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isCheckableDocument(): bool
    {
        return in_array($this->mime_type, self::CHECKABLE_DOCUMENTS, true);
    }

    /** Rodzaj pliku do filtrów i ikon: image, document, other. */
    public function kind(): string
    {
        return match (true) {
            $this->isImage() => 'image',
            in_array(strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp'], true) => 'document',
            default => 'other',
        };
    }
}
