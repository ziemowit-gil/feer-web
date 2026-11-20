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
use Laravel\Sanctum\HasApiTokens;
use Revoltify\Subscriptionify\Concerns\InteractsWithSubscriptions;
use Revoltify\Subscriptionify\Contracts\Subscribable;

#[Fillable(['name', 'email', 'password', 'role', 'user_group_id', 'microsoft_id', 'google_id', 'avatar', 'local_login_allowed', 'notifications_seen_at', 'notification_preferences', 'default_editor', 'admin_scheme', 'admin_nav_style', 'page_mounts', 'active_from', 'active_until', 'expiry_handled_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements Subscribable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    use LogsActivity;
    use InteractsWithSubscriptions;

    public const ROLE_ADMIN          = 'admin';
    public const ROLE_CONTENT_EDITOR = 'content_editor';
    public const ROLE_COORDINATOR    = 'coordinator';
    public const ROLE_PR_EDITOR      = 'pr_editor';
    public const ROLE_BIP_EDITOR_PLUS = 'bip_editor_plus';
    public const ROLE_BIP_EDITOR     = 'bip_editor';
    public const ROLE_EDITOR         = 'editor';
    public const ROLE_NEWSLETTER_EDITOR    = 'newsletter_editor';
    public const ROLE_NEWSLETTER_PUBLISHER = 'newsletter_publisher';

    public const ROLES = [
        self::ROLE_ADMIN           => 'Administrator',
        self::ROLE_CONTENT_EDITOR  => 'Edytor treści',
        self::ROLE_COORDINATOR     => 'Koordynator',
        self::ROLE_PR_EDITOR       => 'Redaktor PR',
        self::ROLE_BIP_EDITOR_PLUS => 'Edytor BIP+',
        self::ROLE_BIP_EDITOR      => 'Edytor BIP',
        self::ROLE_NEWSLETTER_EDITOR    => 'Redaktor newslettera',
        self::ROLE_NEWSLETTER_PUBLISHER => 'Wydawca newslettera',
        self::ROLE_EDITOR          => 'Edytor (grupy)',
    ];

    public const EDITOR_CLASSIC = 'classic';
    public const EDITOR_BLOCKS  = 'blocks';

    /** Domyślny edytor treści stron, aktualności i działań (Mój profil → Domyślny edytor). */
    public const EDITORS = [
        self::EDITOR_CLASSIC => 'Klasyczny (formularz)',
        self::EDITOR_BLOCKS  => 'Blokowy',
    ];

    /**
     * Schematy kolorów panelu (Mój profil → Wygląd panelu): klucz => [nazwa, opis, próbki kolorów].
     * Style w admin/partials/theme-joomla (atrybut data-scheme na <body>); „pierwotny” = kolory dawnego panelu.
     * „metro” to alternatywny styl całego panelu (admin/partials/theme-metro): płaskie kafle, kanciaste elementy, pulpit z kaflami.
     */
    public const ADMIN_SCHEMES = [
        'krem' => ['Krem', 'Ciepłe, jasne tło i kremowe pasy.', ['#f4f4ee', '#f7f7e8', '#d6d6c2']],
        'szary' => ['Szary', 'Neutralne, chłodne szarości — spokojnie i bez koloru.', ['#f3f4f6', '#f9fafb', '#d1d5db']],
        'niebieski' => ['Niebieski', 'Jasne błękity w kolorze marki FEER.', ['#eef3fb', '#f3f7ff', '#c9d6ee']],
        'szalwia' => ['Szałwiowy', 'Delikatna, przygaszona zieleń.', ['#f1f5f1', '#f5f9f4', '#cddbcb']],
        'pierwotny' => ['Pierwotny', 'Kolory dawnego panelu — białe karty na szarym tle.', ['#f3f4f6', '#ffffff', '#e5e7eb']],
        'metro' => ['Metro', 'Alternatywny styl wzorowany na Metro UI: jasne płaskie kafle na pulpicie, kanciaste elementy, jasne menu z akcentem.', ['#f3f3f3', '#1d68f2', '#d7e5ff']],
    ];

    public const DEFAULT_ADMIN_SCHEME = 'krem';

    public function adminScheme(): string
    {
        return array_key_exists((string) $this->admin_scheme, self::ADMIN_SCHEMES) ? $this->admin_scheme : self::DEFAULT_ADMIN_SCHEME;
    }

    /**
     * Styl menu bocznego panelu (Mój profil → Wygląd panelu): grupy wysuwane w dymku obok paska
     * albo rozwijane w miejscu, pod nagłówkiem grupy. Na telefonie menu jest zawsze rozwijane.
     */
    public const ADMIN_NAV_STYLES = [
        'flyout' => ['Wysuwane', 'Pozycje grupy otwierają się w dymku obok paska po najechaniu albo kliknięciu.'],
        'accordion' => ['Rozwijane', 'Pozycje grupy rozwijają się pod jej nazwą, w samym pasku — jedna grupa naraz.'],
    ];

    public const DEFAULT_ADMIN_NAV_STYLE = 'flyout';

    public function adminNavStyle(): string
    {
        return array_key_exists((string) $this->admin_nav_style, self::ADMIN_NAV_STYLES) ? $this->admin_nav_style : self::DEFAULT_ADMIN_NAV_STYLE;
    }

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
            'page_mounts' => 'array',
            'active_from' => 'datetime',
            'active_until' => 'datetime',
            'expiry_handled_at' => 'datetime',
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
    /** Klucze dostępu (passkey / WebAuthn) — logowanie bez hasła. */
    public function webauthnCredentials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebauthnCredential::class)->latest('id');
    }

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

    /** Dodatkowe grupy osoby (user_group_user) — uprawnienia z nich sumują się z grupą główną. */
    public function extraGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_user')->withTimestamps();
    }

    /** Czy tabela dodatkowych grup już istnieje (push wdraża kod przed migracją). */
    public static function supportsExtraGroups(): bool
    {
        static $has = null;

        return $has ??= \Illuminate\Support\Facades\Schema::hasTable('user_group_user');
    }

    /** Wszystkie grupy osoby z roli Edytor (grupa główna + dodatkowe). @return \Illuminate\Support\Collection<int, UserGroup> */
    public function allGroups(): \Illuminate\Support\Collection
    {
        if ($this->role !== self::ROLE_EDITOR) {
            return collect();
        }
        $extra = self::supportsExtraGroups() && $this->exists ? $this->extraGroups : collect();

        return collect([$this->group])->merge($extra)->filter()->unique('id')->values();
    }

    /** Numery wszystkich grup osoby (np. dostęp do stron dla wybranych grup). @return array<int> */
    public function groupIds(): array
    {
        $ids = $this->allGroups()->pluck('id')->all();
        if ($ids === [] && $this->user_group_id) {
            $ids = [(int) $this->user_group_id];
        }

        return array_map('intval', $ids);
    }

    /** Zapamiętane połączone uprawnienia grup (na czas żądania). */
    private ?UserGroup $permissionGroupCache = null;

    private bool $permissionGroupResolved = false;

    /**
     * Uprawnienia z grup połączone w jedną (niezapisywaną) grupę — najszersza reguła wygrywa (jak be_groups w TYPO3):
     * moduły i uprawnienia newslettera się sumują, publikować może, gdy pozwala na to którakolwiek grupa, „tylko
     * własne wpisy”, gdy mają to wszystkie, gałęzie stron i kategorie działań się sumują (grupa z modułem bez
     * ograniczenia znosi ograniczenie), typy bloków się sumują (grupa bez listy = wszystkie), a pole jest
     * zablokowane, tylko gdy blokują je wszystkie grupy.
     */
    public function permissionGroup(): ?UserGroup
    {
        if ($this->permissionGroupResolved) {
            return $this->permissionGroupCache;
        }
        $this->permissionGroupResolved = true;
        $groups = $this->allGroups();
        if ($groups->count() <= 1) {
            return $this->permissionGroupCache = $groups->first();
        }

        $scope = function (string $module, string $key) use ($groups): ?array {
            $granting = $groups->filter(fn (UserGroup $g) => $g->hasModule($module));
            if ($granting->contains(fn (UserGroup $g) => empty($g->{$key}))) {
                return null;
            }
            $ids = $groups->flatMap(fn (UserGroup $g) => (array) ($g->{$key} ?? []))->map(fn ($id) => (int) $id)->unique()->values()->all();

            return $ids ?: null;
        };
        $blockTypes = $groups->contains(fn (UserGroup $g) => empty($g->block_types))
            ? null
            : $groups->flatMap(fn (UserGroup $g) => (array) $g->block_types)->unique()->values()->all();
        $locked = array_values(array_intersect(...$groups->map(fn (UserGroup $g) => (array) ($g->locked_fields ?? []))->all()));

        $merged = new UserGroup;
        $merged->forceFill([
            'name' => $groups->pluck('name')->join(' + '),
            'modules' => $groups->flatMap(fn (UserGroup $g) => (array) ($g->modules ?? []))->unique()->values()->all(),
            'can_approve' => $groups->contains(fn (UserGroup $g) => (bool) $g->can_approve),
            'own_content_only' => $groups->every(fn (UserGroup $g) => (bool) $g->own_content_only),
            'project_category_ids' => $scope('projects', 'project_category_ids'),
            'page_ids' => $scope('pages', 'page_ids'),
            'block_types' => $blockTypes,
            'locked_fields' => $locked ?: null,
        ]);

        return $this->permissionGroupCache = $merged;
    }

    /** Typy bloków edytora, których osoba może używać (null = wszystkie). @return array<int, string>|null */
    public function allowedBlockTypes(): ?array
    {
        if ($this->isAdmin() || $this->role !== self::ROLE_EDITOR) {
            return null;
        }
        $types = $this->permissionGroup()?->block_types;

        return empty($types) ? null : array_values((array) $types);
    }

    /** Pola, których osoba nie zmienia (klucze z EditorPermissions::LOCKABLE_FIELDS). @return array<int, string> */
    public function lockedFields(): array
    {
        if ($this->isAdmin() || $this->role !== self::ROLE_EDITOR) {
            return [];
        }

        return array_values(array_intersect((array) ($this->permissionGroup()?->locked_fields ?? []), array_keys(\App\Support\EditorPermissions::LOCKABLE_FIELDS)));
    }

    // ── Okno ważności konta (jak start/stop w TYPO3) ───────────────────────

    /** 'pending' (jeszcze nieaktywne), 'expired' (wygasło) albo null (aktywne). */
    public function accountWindowState(?\Carbon\CarbonInterface $now = null): ?string
    {
        $now ??= now();
        if ($this->active_from && $now->lt($this->active_from)) {
            return 'pending';
        }
        if ($this->active_until && $now->gte($this->active_until)) {
            return 'expired';
        }

        return null;
    }

    public function isAccountActive(): bool
    {
        return $this->accountWindowState() === null;
    }

    /** Komunikat dla osoby, której konto jest poza oknem ważności. */
    public function accountWindowMessage(): ?string
    {
        return match ($this->accountWindowState()) {
            'pending' => 'Twoje konto będzie aktywne od '.$this->active_from->timezone('Europe/Warsaw')->format('j.m.Y H:i').'. Skontaktuj się z administratorem, jeśli potrzebujesz dostępu wcześniej.',
            'expired' => 'Twoje konto wygasło '.$this->active_until->timezone('Europe/Warsaw')->format('j.m.Y H:i').'. Skontaktuj się z administratorem, jeśli potrzebujesz dalszego dostępu.',
            default => null,
        };
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

    /** Role, którym można przypisać gałęzie stron (redakcja stron). */
    public const PAGE_MOUNT_ROLES = [self::ROLE_EDITOR, self::ROLE_CONTENT_EDITOR];

    /**
     * Gałęzie stron przypisane tej osobie (users.page_mounts, jak „DB mounts” w TYPO3) — tylko dla ról redakcji stron.
     *
     * @return array<int>
     */
    public function personalPageMounts(): array
    {
        if ($this->isAdmin() || ! in_array($this->role, self::PAGE_MOUNT_ROLES, true)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', (array) ($this->page_mounts ?? []))));
    }

    /** Czy edytor z grupy ma ograniczony zakres treści (tylko własne wpisy i/lub wybrane kategorie działań). */
    public function hasContentScope(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }
        if ($this->personalPageMounts() !== []) {
            return true;
        }
        $group = $this->role === self::ROLE_EDITOR ? $this->permissionGroup() : null;
        if (! $group) {
            return false;
        }

        return $group->own_content_only || ! empty($group->project_category_ids) || ! empty($group->page_ids);
    }

    /** Dozwolone kategorie działań edytora (null = bez ograniczenia). */
    /** Zapamiętany zbiór stron dozwolonych dla redaktora (na czas żądania). */
    private ?array $allowedPageIdsCache = null;

    /**
     * Strony, które redaktor może edytować: wybrane w grupie i przypisane osobie strony (działy) wraz ze wszystkimi podstronami.
     * null = bez ograniczeń (administrator albo grupa bez wskazanych stron).
     *
     * @return array<int>|null
     */
    public function allowedPageIds(): ?array
    {
        if ($this->isAdmin() || ! in_array($this->role, self::PAGE_MOUNT_ROLES, true)) {
            return null;
        }
        // Gałęzie z grupy (rola Edytor) i przypisane osobie — sumują się.
        $groupRoots = $this->role === self::ROLE_EDITOR ? (array) ($this->permissionGroup()?->page_ids ?? []) : [];
        $roots = array_values(array_unique(array_filter(array_map('intval', [...$groupRoots, ...$this->personalPageMounts()]))));
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
        $ids = array_map('intval', (array) ($this->permissionGroup()?->project_category_ids ?? []));

        return $ids === [] ? null : $ids;
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Newsletter ma własne, drobniejsze uprawnienia — wejście do modułu daje każde z nich.
        if ($module === 'newsletter') {
            return $this->hasNewsletterPermission(\App\Support\NewsletterPermissions::VIEW);
        }

        return match ($this->role) {
            self::ROLE_CONTENT_EDITOR  => true,
            self::ROLE_COORDINATOR     => in_array($module, ['volunteering', 'jobs', 'events', 'courses', 'materials', 'podcasts'], true),
            self::ROLE_PR_EDITOR       => in_array($module, ['news', 'blog', 'landing', 'projects', 'polls', 'podcasts'], true),
            self::ROLE_BIP_EDITOR_PLUS => in_array($module, ['bip', 'reports'], true),
            self::ROLE_BIP_EDITOR      => $module === 'bip',
            self::ROLE_EDITOR          => (bool) $this->permissionGroup()?->hasModule($module),
            default                    => false,
        };
    }

    /** Uprawnienie modułu Newsletter (klucz z {@see \App\Support\NewsletterPermissions}). */
    public function hasNewsletterPermission(string $permission): bool
    {
        return \App\Support\NewsletterPermissions::allows($this, $permission);
    }

    /**
     * Czy użytkownik może zatwierdzać i publikować treść. Admin zawsze może;
     * pozostali — tylko gdy ich grupa ma uprawnienie moderatora/akceptanta.
     * Edytor bez tego uprawnienia zgłasza treść „do zatwierdzenia".
     */
    public function canApproveContent(): bool
    {
        return $this->isAdmin() || (bool) $this->permissionGroup()?->can_approve;
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
            if (self::supportsExtraGroups()) {
                $q->orWhere(fn ($e) => $e->where('role', self::ROLE_EDITOR)->whereHas('extraGroups', fn ($g) => $g->where('can_approve', true)));
            }
        });
    }

    /** Edytor, który „Edytuj” otwiera domyślnie: wybrany w profilu, a bez wyboru — blokowy. */
    public function effectiveEditor(): string
    {
        return array_key_exists((string) $this->default_editor, self::EDITORS) ? $this->default_editor : self::EDITOR_BLOCKS;
    }

    /** Czy „Edytuj” ma otwierać edytor blokowy (domyślnie tak; klasyczny trzeba wybrać w profilu; wymaga modułu w Ustawienia → Moduły). */
    public function prefersBlockEditor(): bool
    {
        return $this->effectiveEditor() === self::EDITOR_BLOCKS
            && SiteSetting::current()->isModuleEnabled('block_editor');
    }

    /** Pytanie o domyślny edytor wyłączone: domyślny jest blokowy, a zmianę robi się w Mój profil → Domyślny edytor. */
    public function needsDefaultEditorChoice(): bool
    {
        return false;
    }
}
