<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

/**
 * Dowolne bloki w stałym miejscu strony listy (np. `projects_sidebar` — pod menu kategorii
 * na stronie „Działania”). Edytowane w edytorze blokowym: bloki w `blocks`, HTML w `content`.
 */
class ListingSection extends Model
{
    use BelongsToSite;

    /** Dostępne miejsca: klucz => [tytuł w edytorze, moduł uprawnień, trasa strony publicznej]. */
    public const PLACES = [
        'projects_sidebar' => ['title' => 'Działania — pod menu kategorii', 'module' => 'projects', 'route' => 'projects.index'],
    ];

    protected $fillable = ['site_id', 'key', 'title', 'meta_description', 'is_published', 'blocks', 'content'];

    protected $casts = ['blocks' => 'array', 'is_published' => 'boolean'];

    /** Rekord miejsca dla bieżącej witryny (tworzony przy pierwszej edycji). */
    public static function place(string $key): self
    {
        abort_unless(isset(self::PLACES[$key]), 404);

        return static::query()->forCurrentSite()->firstOrCreate(['key' => $key], ['title' => self::PLACES[$key]['title']]);
    }

    /** HTML do wyświetlenia albo null, gdy miejsce jest puste lub ukryte. */
    public static function html(string $key): ?string
    {
        $section = static::query()->forCurrentSite()->where('key', $key)->first();

        return $section && $section->is_published && filled(trim(strip_tags((string) $section->content, '<img><iframe><video><audio><svg><hr>')))
            ? (string) $section->content
            : null;
    }

    public function publicUrl(): string
    {
        return route(self::PLACES[$this->key]['route'] ?? 'home');
    }
}
