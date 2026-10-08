<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Blok treści zbudowany w kreatorze edytora (przyciski CTA albo akordeon); osadzany shortcodem [blok:ID].
 */
class ContentBlock extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    public const TYPES = ['cta' => 'Przyciski CTA', 'accordion' => 'Akordeon'];

    protected $fillable = ['site_id', 'type', 'name', 'data', 'created_by'];

    protected $casts = ['data' => 'array'];
}
