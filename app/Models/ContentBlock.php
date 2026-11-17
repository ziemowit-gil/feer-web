<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Blok treści zbudowany w kreatorze edytora (przyciski CTA albo akordeon); osadzany shortcodem [blok:ID].
 */
class ContentBlock extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    public const TYPES = ['cta' => 'Przyciski CTA', 'accordion' => 'Akordeon', 'callout' => 'Ramka informacyjna', 'pricing' => 'Cennik'];

    public const CALLOUT_VARIANTS = ['blue' => 'Niebieska', 'gold' => 'Złota', 'red' => 'Czerwona', 'green' => 'Zielona'];

    public const CALLOUT_ICONS = ['exclamation' => 'fa-exclamation', 'info' => 'fa-info', 'coins' => 'fa-coins', 'warning' => 'fa-triangle-exclamation', 'check' => 'fa-check'];

    /**
     * Blok wspólny z edytora blokowego (jak „Wstaw rekord” w TYPO3): data.blocks to bloki edytora, osadzane na wielu
     * stronach shortcodem [blok:ID] i renderowane przy wyświetlaniu — zmiana w jednym miejscu działa wszędzie.
     * Poza TYPES, żeby kreator klasycznego edytora go nie pokazywał ani nie nadpisywał.
     */
    public const SHARED = 'blocks';

    protected $fillable = ['site_id', 'type', 'name', 'data', 'created_by'];

    public function isShared(): bool
    {
        return $this->type === self::SHARED;
    }

    /** Strony bieżącej witryny, w których treści jest ten blok ([blok:ID]). */
    public function usedOnPages(): \Illuminate\Support\Collection
    {
        return Page::forCurrentSite()->where('content', 'like', '%[blok:'.$this->id.']%')->orderBy('title')->get(['id', 'title']);
    }

    protected $casts = ['data' => 'array'];
}
