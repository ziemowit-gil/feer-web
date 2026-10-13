<?php

namespace App\Support;

use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Przegląd treści: opublikowane strony, aktualności i działania, których nikt nie aktualizował
 * (ani nie oznaczył jako sprawdzone) od N miesięcy, oraz automatyczne zdejmowanie z publikacji.
 */
class ContentReview
{
    public const DEFAULT_MONTHS = 12;

    /** Typy treści: klasa, etykieta, moduł (uprawnienia), trasa edycji. */
    public const SOURCES = [
        'pages' => ['model' => Page::class, 'label' => 'Strona', 'module' => 'pages', 'route' => 'admin.podstrony.edit'],
        'news' => ['model' => News::class, 'label' => 'Aktualność', 'module' => 'news', 'route' => 'admin.newsy.edit'],
        'projects' => ['model' => Project::class, 'label' => 'Działanie', 'module' => 'projects', 'route' => 'admin.projekty.edit'],
    ];

    /** Czy migracja z kolumnami przeglądu już weszła (przed deploy.sh — nie). */
    public static function available(): bool
    {
        return once(fn () => Schema::hasColumn('pages', 'reviewed_at') && Schema::hasColumn('news', 'reviewed_at'));
    }

    /**
     * Treści do przejrzenia, od najdłużej nieaktualizowanych.
     *
     * @return Collection<int, array{key: string, type: string, model: Model, last: \Illuminate\Support\Carbon, edit: ?string}>
     */
    public static function stale(int $months = self::DEFAULT_MONTHS, ?callable $canAccess = null): Collection
    {
        if (! self::available()) {
            return collect();
        }
        $cutoff = now()->subMonths($months);
        $rows = collect();

        foreach (self::SOURCES as $key => $source) {
            if ($canAccess && ! $canAccess($source['module'])) {
                continue;
            }
            /** @var Builder $query */
            $query = $source['model']::query()->forCurrentSite()->where('is_published', true)
                ->where('updated_at', '<', $cutoff)
                ->where(fn ($q) => $q->whereNull('reviewed_at')->orWhere('reviewed_at', '<', $cutoff));
            if ($key === 'pages') {
                $query->whereNotIn('type', ['redirect', 'menu_link', 'menu_heading'])->where('is_system', false);
            }
            if ($key === 'news') {
                $query->where('is_archived', false); // archiwalne mają baner „treść może być nieaktualna”
            }
            foreach ($query->get() as $model) {
                $last = $model->reviewed_at && $model->reviewed_at->gt($model->updated_at) ? $model->reviewed_at : $model->updated_at;
                $rows->push([
                    'key' => $key,
                    'type' => $source['label'],
                    'model' => $model,
                    'last' => $last,
                    'edit' => Route::has($source['route']) ? route($source['route'], $model) : null,
                ]);
            }
        }

        return $rows->sortBy(fn ($r) => $r['last']->timestamp)->values();
    }

    /** Liczba do przejrzenia (licznik w menu) — w pamięci podręcznej na godzinę. */
    public static function staleCount(?callable $canAccess = null): int
    {
        return (int) Cache::remember(self::countKey(), now()->addHour(),
            fn () => self::stale(self::DEFAULT_MONTHS, $canAccess)->count());
    }

    public static function forgetCount(): void
    {
        Cache::forget(self::countKey());
    }

    /** Klucz licznika: witryna i użytkownik (każdy widzi tylko swoje moduły). */
    private static function countKey(): string
    {
        return 'content-review-count:'.(int) (\App\Models\SiteSetting::current()->id ?? 0).':'.(int) auth()->id();
    }

    /** Oznacza treść jako sprawdzoną — bez zmiany daty aktualizacji i bez wersji w historii. */
    public static function markReviewed(Model $model): void
    {
        $model->timestamps = false;
        $model->forceFill(['reviewed_at' => now()])->saveQuietly();
        $model->timestamps = true;
        self::forgetCount();
    }

    /**
     * Zdejmuje z publikacji treści, których termin minął. Data jest czyszczona, żeby ponowne
     * opublikowanie przez redaktora nie zostało od razu cofnięte.
     *
     * @return array<string, int> liczba zdjętych wg typu
     */
    public static function unpublishDue(): array
    {
        if (! Schema::hasColumn('pages', 'unpublish_at')) {
            return [];
        }
        $done = [];
        foreach (self::SOURCES as $key => $source) {
            // Wszystkie witryny (bez zakresu bieżącej), ale bez treści w koszu.
            $query = $source['model']::query()->withoutGlobalScopes();
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($source['model']), true)) {
                $query->whereNull($query->getModel()->getTable().'.deleted_at');
            }
            $items = $query->where('is_published', true)
                ->whereNotNull('unpublish_at')->where('unpublish_at', '<=', now())->get();
            foreach ($items as $item) {
                $item->forceFill(['is_published' => false, 'unpublish_at' => null])->save();
                activity('cms')->performedOn($item)->withProperty('label', $item->title)
                    ->event('unpublished_on_schedule')->log('Zdjęto z publikacji zgodnie z terminem');
            }
            $done[$key] = $items->count();
        }

        return $done;
    }
}
