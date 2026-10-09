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

#[Fillable(['name', 'email', 'password', 'role', 'user_group_id', 'microsoft_id', 'google_id', 'avatar', 'local_login_allowed', 'notifications_seen_at', 'notification_preferences'])]
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
            'is_super_admin' => 'boolean',
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

    /**
     * Konta na domenach demo.* (dostęp demonstracyjny) oraz konto serwisowe
     * @local nie wymagają 2FA.
     */
    public function isDemoAccount(): bool
    {
        $domain = substr(strrchr($this->email, '@'), 1);
        return str_starts_with($domain, 'demo.') || $domain === 'local';
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

    /** Czy edytor z grupy ma ograniczony zakres treści (tylko własne wpisy i/lub wybrane kategorie działań). */
    public function hasContentScope(): bool
    {
        if ($this->isAdmin() || $this->role !== self::ROLE_EDITOR || ! $this->group) {
            return false;
        }

        return $this->group->own_content_only || ! empty($this->group->project_category_ids) || ! empty($this->group->page_ids);
    }

    /** Dozwolone kategorie działań edytora (null = bez ograniczenia). */
    /** Zapamiętany zbiór stron dozwolonych dla redaktora (na czas żądania). */
    private ?array $allowedPageIdsCache = null;

    /**
     * Strony, które redaktor może edytować: wybrane w grupie strony (działy) wraz ze wszystkimi podstronami.
     * null = bez ograniczeń (administrator albo grupa bez wskazanych stron).
     *
     * @return array<int>|null
     */
    public function allowedPageIds(): ?array
    {
        if ($this->isAdmin() || $this->role !== self::ROLE_EDITOR) {
            return null;
        }
        $roots = array_values(array_filter(array_map('intval', (array) ($this->group?->page_ids ?? []))));
        if ($roots === []) {
            return null;
        }
        if ($this->allowedPageIdsCache !== null) {
            return $this->allowedPageIdsCache;
        }

        // Bez zakresu redaktora — inaczej zapytanie wywołałoby ten sam zakres (rekurencja).
        $pairs = \App\Models\Page::withoutGlobalScope('editor_scope')->forCurrentSite()->pluck('parent_id', 'id');
        $children = [];
        foreach ($pairs as $id => $parentId) {
            $children[(int) $parentId][] = (int) $id;
        }
        $allowed = [];
        $stack = $roots;
        while ($stack) {
            $id = array_pop($stack);
            if (isset($allowed[$id])) {
                continue;
            }
            $allowed[$id] = true;
            foreach ($children[$id] ?? [] as $child) {
                $stack[] = $child;
            }
        }

        return $this->allowedPageIdsCache = array_map('intval', array_keys($allowed));
    }

    public function allowedProjectCategoryIds(): ?array
    {
        if ($this->isAdmin() || $this->role !== self::ROLE_EDITOR) {
            return null;
        }
        $ids = array_map('intval', (array) ($this->group?->project_category_ids ?? []));

        return $ids === [] ? null : $ids;
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return match ($this->role) {
            self::ROLE_CONTENT_EDITOR  => true,
            self::ROLE_COORDINATOR     => in_array($module, ['volunteering', 'jobs', 'events', 'materials', 'podcasts'], true),
            self::ROLE_PR_EDITOR       => in_array($module, ['news', 'blog', 'landing', 'projects', 'polls', 'podcasts'], true),
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
