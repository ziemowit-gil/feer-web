<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickAction extends Model
{
    protected $fillable = ['label', 'icon', 'url', 'order', 'color', 'is_negative', 'cols', 'strip'];

    protected $casts = ['is_negative' => 'boolean', 'cols' => 'integer', 'strip' => 'boolean'];

    /** Klasy Tailwind col-span dla danej liczby kolumn. */
    public function colSpanClass(): string
    {
        return match ((int) ($this->cols ?? 1)) {
            2 => 'col-span-2',
            3 => 'col-span-2 sm:col-span-3',
            default => '',
        };
    }
}
