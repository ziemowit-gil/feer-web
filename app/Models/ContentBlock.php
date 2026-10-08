<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Blok treści zbudowany w kreatorze edytora (przyciski CTA albo akordeon); osadzany shortcodem [blok:ID].
 */
class ContentBlock extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    public const TYPES = ['cta' => 'Przyciski CTA', 'accordion' => 'Akordeon', 'callout' => 'Ramka informacyjna'];

    public const CALLOUT_VARIANTS = ['blue' => 'Niebieska', 'gold' => 'Złota', 'red' => 'Czerwona', 'green' => 'Zielona'];

    public const CALLOUT_ICONS = ['exclamation' => 'fa-exclamation', 'info' => 'fa-info', 'coins' => 'fa-coins', 'warning' => 'fa-triangle-exclamation', 'check' => 'fa-check'];

    protected $fillable = ['site_id', 'type', 'name', 'data', 'created_by'];

    protected $casts = ['data' => 'array'];
}
