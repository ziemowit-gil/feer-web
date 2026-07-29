<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'user_group_id', 'microsoft_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EDITOR = 'editor';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    /**
     * Admins always have full access; editors only have access to the
     * content modules their assigned group was granted.
     */
    public function canAccessModule(string $module): bool
    {
        return $this->isAdmin() || ($this->group && $this->group->hasModule($module));
    }

    /**
     * Czy użytkownik może zatwierdzać i publikować treść. Admin zawsze może;
     * pozostali — tylko gdy ich grupa ma uprawnienie moderatora/akceptanta.
     * Edytor bez tego uprawnienia zgłasza treść „do zatwierdzenia".
     */
    public function canApproveContent(): bool
    {
        return $this->isAdmin() || ($this->group && $this->group->can_approve);
    }
}
