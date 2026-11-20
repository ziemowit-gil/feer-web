<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    use \App\Models\Concerns\LogsActivity;

    protected $fillable = ['name', 'modules', 'can_approve', 'own_content_only', 'project_category_ids', 'page_ids', 'block_types', 'locked_fields'];

    protected $casts = [
        'modules' => 'array',
        'can_approve' => 'boolean',
        'own_content_only' => 'boolean',
        'project_category_ids' => 'array',
        'page_ids' => 'array',
        // Dozwolone typy bloków edytora (puste = wszystkie) i pola, których grupa nie zmienia (EditorPermissions).
        'block_types' => 'array',
        'locked_fields' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Osoby z tą grupą jako dodatkową (user_group_user). */
    public function extraUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_user')->withTimestamps();
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules ?? [], true);
    }
}
