<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Slider (zestaw slajdów): strona główna, blog albo wybrane strony (layout_data.hero_slider_id strony).
 * Slajdy to HeroSlide z slider_id.
 */
class Slider extends Model
{
    use \App\Models\Concerns\BelongsToSite;
    use \App\Models\Concerns\LogsActivity;

    protected $fillable = ['site_id', 'name', 'is_home', 'show_on_blog', 'duration'];

    protected $casts = ['is_home' => 'boolean', 'show_on_blog' => 'boolean', 'duration' => 'integer'];

    public function slides(): HasMany
    {
        return $this->hasMany(HeroSlide::class)->orderBy('order')->orderBy('id');
    }

    /** Strony, które pokazują ten slider (wybór w edycji strony → Typ i układ). */
    public function pages()
    {
        return Page::query()->forCurrentSite()->where('layout_data->hero_slider_id', $this->id)->orderBy('title')->get(['id', 'title', 'slug']);
    }

    /** Opis miejsc wyświetlania, np. „Strona główna, Blog, 2 strony”. */
    public function placementLabel(): string
    {
        $parts = array_filter([$this->is_home ? 'Strona główna' : null, $this->show_on_blog ? 'Blog' : null]);
        $pages = $this->pages();
        if ($pages->isNotEmpty()) {
            $parts[] = $pages->count() === 1 ? 'strona „'.$pages->first()->title.'”' : $pages->count().' '.trans_choice('strona|strony|stron', $pages->count());
        }

        return $parts ? implode(', ', $parts) : 'Nigdzie (nieprzypisany)';
    }

    /** Slider strony głównej / bloga dla bieżącej witryny. */
    public static function forPlacement(string $placement): ?self
    {
        $column = $placement === 'blog' ? 'show_on_blog' : 'is_home';

        return static::query()->forCurrentSite()->where($column, true)->orderBy('id')->first();
    }
}
