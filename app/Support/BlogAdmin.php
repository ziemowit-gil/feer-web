<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\Route;
use Modules\Blog\Models\BlogComment;

/**
 * Kontekst „Wiem FEER" w panelu admina: czy aktywna jest witryna bloga, czy bieżąca
 * trasa należy do bloga, czy użytkownik ma dostęp do modułu — wspólne dla pulpitu
 * bloga, menu bocznego (AdminMenu), paska „Szybko:” i podnawigacji bloga.
 *
 * Trasy adminowe modułu bloga nie przechodzą przez middleware `admin-site`, więc
 * aktywną witrynę sprawdzamy także bezpośrednio w sesji (admin_active_site_id).
 */
final class BlogAdmin
{
    /** Trasy panelu należące do bloga (wzorce `routeIs`). */
    public const ROUTES = [
        'admin.wiem.dashboard',
        'admin.wiem-feer.*',
        'admin.kategorie-bloga.*',
        'admin.autorzy-bloga.*',
        'admin.komentarze-bloga.*',
    ];

    /** Slug witryny sieci, na której działa blog. */
    public static function siteSlug(): string
    {
        return (string) config('blog.site_slug', 'wiem');
    }

    /** Czy w panelu wybrano witrynę bloga (przełącznik witryn). */
    public static function isBlogSiteActive(): bool
    {
        $slug = self::siteSlug();

        if (app()->bound(SiteSetting::CURRENT_SITE_BINDING) && SiteSetting::current()->slug === $slug) {
            return true;
        }

        $siteId = session('admin_active_site_id');

        return $siteId !== null && SiteSetting::whereKey($siteId)->where('slug', $slug)->exists();
    }

    /** Czy bieżące żądanie to ekran bloga w panelu. */
    public static function isBlogRoute(): bool
    {
        return request()->routeIs(self::ROUTES);
    }

    /** Moduł bloga aktywny i dostępny dla roli użytkownika. */
    public static function canAccess(?User $user): bool
    {
        return $user !== null
            && app(ModuleManager::class)->isActive('blog')
            && $user->canAccessModule('blog')
            && Route::has('admin.wiem-feer.index');
    }

    /** Czy panel ma działać w trybie „Wiem FEER" (pasek szybkich akcji, podnawigacja). */
    public static function inContext(?User $user): bool
    {
        return self::canAccess($user) && (self::isBlogRoute() || self::isBlogSiteActive());
    }

    /** Liczba komentarzy czekających na moderację (0, gdy baza bloga jest niedostępna). */
    public static function pendingCommentsCount(): int
    {
        try {
            return BlogComment::pending()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Publiczny adres bloga (/wiem). */
    public static function publicUrl(): string
    {
        return Route::has('blog.index')
            ? route('blog.index')
            : url('/'.trim((string) config('blog.prefix', 'wiem'), '/'));
    }
}
