<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AnnualReport extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Dwa rodzaje sprawozdań (klucz = nazwa kolekcji mediów / prefiks kolumn).
     */
    public const TYPES = [
        'substantive' => 'Sprawozdanie merytoryczne',
        'financial' => 'Sprawozdanie finansowe',
    ];

    /**
     * Statusy pojedynczego sprawozdania. „published" oznacza wgrany plik PDF;
     * pozostałe to powody, dla których pliku (jeszcze) nie ma.
     */
    public const STATUSES = [
        'published' => 'Opublikowane (plik do pobrania)',
        'not_yet' => 'Jeszcze nieopublikowane',
        'soon' => 'Dokumenty zostaną niebawem uzupełnione',
        'not_required' => 'Brak obowiązku złożenia sprawozdania',
        'custom' => 'Własny powód',
    ];

    protected $fillable = [
        'year', 'substantive_status', 'substantive_reason',
        'financial_status', 'financial_reason', 'is_published',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_published' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('year');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('substantive')->singleFile();
        $this->addMediaCollection('financial')->singleFile();
        // Dodatkowe pliki (0..n): uchwały, opinie biegłego, załączniki itp.
        $this->addMediaCollection('additional');
    }

    /** Status wybranego sprawozdania ('substantive'|'financial'). */
    public function statusFor(string $type): string
    {
        return $this->{$type.'_status'} ?? 'not_yet';
    }

    /** URL pliku PDF danego sprawozdania (null, gdy nie wgrano). */
    public function fileUrlFor(string $type): ?string
    {
        return $this->getFirstMediaUrl($type) ?: null;
    }

    /**
     * Tekst wyświetlany, gdy sprawozdania nie da się pobrać (brak pliku).
     * Zwraca null tylko wtedy, gdy plik istnieje i można go pobrać.
     */
    public function messageFor(string $type): ?string
    {
        $status = $this->statusFor($type);

        if ($status === 'published') {
            // „Opublikowane" bez wgranego pliku traktujemy jak „wkrótce",
            // żeby nigdy nie pokazać pustej komórki.
            return $this->fileUrlFor($type) ? null : 'Dokumenty zostaną niebawem uzupełnione.';
        }

        return match ($status) {
            'not_yet' => "Sprawozdanie za {$this->year} rok nie zostało jeszcze opublikowane.",
            'soon' => 'Dokumenty zostaną niebawem uzupełnione.',
            'not_required' => 'Organizacja nie ma obowiązku składania tego sprawozdania.',
            'custom' => trim((string) $this->{$type.'_reason'}) ?: 'Sprawozdanie niedostępne.',
            default => 'Sprawozdanie niedostępne.',
        };
    }

    /** Dodatkowe pliki danego roku (posortowane wg kolejności wgrania). */
    public function additionalFiles(): Collection
    {
        return $this->getMedia('additional');
    }

    /**
     * Ikona Font Awesome dla pliku dodatkowego wg rozszerzenia.
     */
    public function fileIcon(Media $media): string
    {
        return match (strtolower($media->extension)) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx', 'odt' => 'fa-file-word',
            'xls', 'xlsx', 'ods', 'csv' => 'fa-file-excel',
            'zip', '7z', 'rar' => 'fa-file-zipper',
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'fa-file-image',
            default => 'fa-file-lines',
        };
    }
}
