<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'order'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function publishedProjects(): HasMany
    {
        return $this->projects()->where('is_published', true)->forCurrentSite()->orderBy('order');
    }
}
