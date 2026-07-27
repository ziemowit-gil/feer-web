<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_url', 'is_active', 'hits'];

    protected $casts = [
        'is_active' => 'boolean',
        'hits' => 'integer',
    ];

    /**
     * Znormalizowana ścieżka źródłowa: pojedynczy wiodący ukośnik, bez ukośnika
     * końcowego i bez parametrów zapytania — tak porównujemy z żądaniem.
     */
    public static function normalizePath(string $path): string
    {
        $path = trim(explode('?', $path, 2)[0]);
        $path = '/'.ltrim($path, '/');

        return $path !== '/' ? rtrim($path, '/') : '/';
    }
}
