<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Revoltify\Subscriptionify\Concerns\InteractsWithSubscriptions;
use Revoltify\Subscriptionify\Contracts\Subscribable;

#[Fillable(['name', 'email', 'password', 'role', 'user_group_id', 'microsoft_id', 'avatar', 'local_login_allowed', 'notifications_seen_at', 'notification_preferences'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements Subscribable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use LogsActivity;
    use InteractsWithSubscriptions;

    public const ROLE_ADMIN          = 'admin';
    public const ROLE_CONTENT_EDITOR = 'content_editor';
    public const ROLE_COORDINATOR    = 'coordinator';
    public const ROLE_PR_EDITOR      = 'pr_editor';
    public const ROLE_BIP_EDITOR_PLUS = 'bip_editor_plus';
    public const ROLE_BIP_EDITOR     = 'bip_editor';
    public const ROLE_EDITOR         = 'editor';

    public const ROLES = [
        self::ROLE_ADMIN           => 'Administrator',
        self::ROLE_CONTENT_EDITOR  => 'Edytor treści',
        self::ROLE_COORDINATOR     => 'Koordynator',
        self::ROLE_PR_EDITOR       => 'Redaktor PR',
        self::ROLE_BIP_EDITOR_PLUS => 'Edytor BIP+',
        self::ROLE_BIP_EDITOR      => 'Edytor BIP',
        self::ROLE_EDITOR          => 'Edytor (grupy)',
    ];

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
            'local_login_allowed' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'yubikey_ids' => 'array',
            'notifications_seen_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Czy aplikacja TOTP (Google Authenticator) jest skonfigurowana i potwierdzona. */
    public function hasTotpEnabled(): bool
    {
        return filled($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /** Czy zarejestrowano co najmniej jeden klucz YubiKey. */
    public function hasYubikey(): bool
    {
        return ! empty($this->yubikey_ids);
    }

    /** Czy jakakolwiek metoda 2FA jest aktywna. */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->hasTotpEnabled() || $this->hasYubikey();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    /**
     * Admins always have full access; editors only have access to the
     * content modules their assigned group was granted.
     */
    public function isBipEditor(): bool
    {
        return in_array($this->role, [self::ROLE_BIP_EDITOR, self::ROLE_BIP_EDITOR_PLUS], true);
    }

    public function isCoordinator(): bool
    {
        return $this->role === self::ROLE_COORDINATOR;
    }

    public function isPrEditor(): bool
    {
        return $this->role === self::ROLE_PR_EDITOR;
    }

    public function isContentEditor(): bool
    {
        return $this->role === self::ROLE_CONTENT_EDITOR;
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return match ($this->role) {
            self::ROLE_CONTENT_EDITOR  => true,
            self::ROLE_COORDINATOR     => in_array($module, ['volunteering', 'jobs', 'events', 'materials'], true),
            self::ROLE_PR_EDITOR       => in_array($module, ['news', 'blog', 'landing', 'projects', 'polls'], true),
            self::ROLE_BIP_EDITOR_PLUS => in_array($module, ['bip', 'reports'], true),
            self::ROLE_BIP_EDITOR      => $module === 'bip',
            self::ROLE_EDITOR          => $this->group && $this->group->hasModule($module),
            default                    => false,
        };
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

    /**
     * Użytkownicy uprawnieni do akceptacji treści: administratorzy oraz
     * członkowie grup z uprawnieniem `can_approve`. Odbiorcy powiadomień
     * o treści zgłoszonej do zatwierdzenia.
     */
    public function scopeApprovers(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) {
            $q->where('role', self::ROLE_ADMIN)
                ->orWhereHas('group', fn ($g) => $g->where('can_approve', true));
        });
    }
}
