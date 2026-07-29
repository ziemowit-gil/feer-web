<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    use \App\Models\Concerns\LogsActivity;

    protected $fillable = ['name', 'modules', 'can_approve'];

    protected $casts = [
        'modules' => 'array',
        'can_approve' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules ?? [], true);
    }
}
