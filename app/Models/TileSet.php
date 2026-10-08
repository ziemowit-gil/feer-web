<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Zestaw kafelków zbudowany w kreatorze edytora treści; osadzany w treści shortcodem [kafelki-zestaw:ID].
 */
class TileSet extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    protected $fillable = ['site_id', 'name', 'tiles', 'created_by'];

    protected $casts = ['tiles' => 'array'];
}
