<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Dokument Biuletynu Informacji Publicznej.
 *
 * Wymogi ustawowe BIP: autor wprowadzenia, data dodania i ostatniej zmiany
 * przechowywane są w polach `created_by`, `created_at`, `updated_by`,
 * `updated_at`. Historia wersji (HasRevisions) umożliwia podgląd zmian
 * i ich cofanie. SoftDeletes zapewnia archiwizację usuniętych treści.
 */
class BipDocument extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;
    use \App\Models\Concerns\HasRevisions;
    use \App\Models\Concerns\LogsActivity;

    /**
     * Kategorie dokumentów BIP według struktury wymaganej przepisami prawa.
     */
    public const CATEGORIES = [
        'organizational' => 'Dane organizacyjne i kontakt',
        'statute'        => 'Statut i dokumenty prawne',
        'reports'        => 'Sprawozdania i budżet',
        'projects'       => 'Projekty i działania',
        'announcements'  => 'Ogłoszenia i komunikaty',
        'property'       => 'Majątek i gospodarka',
        'contracts'      => 'Rejestr umów',
        'vacancies'      => 'Rekrutacja i wolontariat',
        'other'          => 'Inne',
    ];

    protected $fillable = [
        'title', 'slug', 'category', 'content', 'summary',
        'is_published', 'published_at', 'order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'order'        => 'integer',
    ];

    public function revisionFields(): array
    {
        return ['title', 'slug', 'category', 'content', 'summary'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['other'];
    }

    /** Ikona Font Awesome dla pliku wg rozszerzenia. */
    public function fileIcon(Media $media): string
    {
        return match (strtolower($media->extension)) {
            'pdf'                        => 'fa-file-pdf',
            'doc', 'docx', 'odt'         => 'fa-file-word',
            'xls', 'xlsx', 'ods', 'csv'  => 'fa-file-excel',
            'zip', '7z', 'rar'           => 'fa-file-zipper',
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'fa-file-image',
            default                      => 'fa-file-lines',
        };
    }

    /** Pliki załączone do dokumentu. */
    public function attachedFiles(): Collection
    {
        return $this->getMedia('files');
    }

    protected static function booted(): void
    {
        static::creating(function (BipDocument $doc) {
            $doc->created_by ??= auth()->id();
            $doc->updated_by = auth()->id();
            if ($doc->is_published && ! $doc->published_at) {
                $doc->published_at = now();
            }
        });

        static::updating(function (BipDocument $doc) {
            $doc->updated_by = auth()->id();
            if ($doc->is_published && ! $doc->published_at) {
                $doc->published_at = now();
            }
        });
    }
}
