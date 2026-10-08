<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Wpis publicznego rejestru pełnomocnictw i upoważnień. Wpisy można dodawać
 * w panelu (Admin\AuthorizationController) lub zasilać z systemu zewnętrznego;
 * strona publiczna służy do przeglądania i weryfikacji statusu ważności.
 */
class Authorization extends Model
{
    use LogsActivity;

    public const TYPES = [
        'pelnomocnictwo' => 'Pełnomocnictwo',
        'upowaznienie'   => 'Upoważnienie',
    ];

    /** Status ważności dokumentu → polska etykieta (kolory dobiera widok). */
    public const STATUSES = [
        'active'   => 'Aktywne',
        'upcoming' => 'Obowiązuje od',
        'expired'  => 'Wygasło',
        'revoked'  => 'Unieważnione',
    ];

    protected $fillable = [
        'type', 'document_number', 'principal', 'grantee_name',
        'scope', 'valid_from', 'valid_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to'   => 'date',
            'is_active'  => 'boolean',
        ];
    }

    /** Etykieta wpisu w dzienniku zdarzeń. */
    public function activityLabel(): string
    {
        return $this->document_number . ' — ' . $this->grantee_name;
    }

    /** Polska etykieta rodzaju dokumentu. */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Status ważności liczony na dziś: unieważnienie ma pierwszeństwo,
     * potem okres obowiązywania (przyszły / miniony / trwający).
     */
    public function status(): string
    {
        if (! $this->is_active) {
            return 'revoked';
        }
        if ($this->valid_from->isFuture()) {
            return 'upcoming';
        }
        if ($this->valid_to !== null && $this->valid_to->endOfDay()->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    /** Proste wyszukiwanie: nazwisko/nazwa, numer dokumentu lub zakres. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim($term)) . '%';

        return $query->where(fn (Builder $q) => $q
            ->where('grantee_name', 'like', $like)
            ->orWhere('document_number', 'like', $like)
            ->orWhere('scope', 'like', $like));
    }
}
