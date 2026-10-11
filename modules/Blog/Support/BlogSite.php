<?php

namespace Modules\Blog\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Witryna sieci bloga Wiem FEER (slug `config('blog.site_slug')`, domyślnie „wiem")
 * i jej własna domena (pole `domain`, np. wiem.feer.org.pl).
 *
 * Na własnej domenie (ResolveSiteByDomain wiąże witrynę po hoście):
 *  - `/` pokazuje listę wpisów (HomeController → BlogController::index),
 *  - `/{slug}` pokazuje wpis, gdy pod tym adresem nie ma strony witryny
 *    (ServeBlogOnOwnDomain).
 * Adresy kanoniczne i linki pozostają w postaci /wiem/… (działają na każdej domenie).
 */
final class BlogSite
{
    public static function slug(): string
    {
        return trim((string) config('blog.site_slug', 'wiem'));
    }

    /** Czy podana witryna to witryna bloga. */
    public static function is(?SiteSetting $site): bool
    {
        $slug = self::slug();

        return $site !== null && $slug !== '' && $site->slug === $slug;
    }

    /** Bieżąca witryna (już związana w kontenerze) albo null — bez wczytywania domyślnej. */
    public static function bound(): ?SiteSetting
    {
        $site = app()->bound(SiteSetting::CURRENT_SITE_BINDING) ? app(SiteSetting::CURRENT_SITE_BINDING) : null;

        return $site instanceof SiteSetting ? $site : null;
    }

    /**
     * Czy żądanie przyszło na własną domenę witryny bloga: związana witryna to blog,
     * ma ustawioną domenę i jest ona hostem żądania. Na domenie głównej zawsze false.
     */
    public static function servesOwnDomain(Request $request): bool
    {
        $site = self::bound();

        return self::is($site)
            && filled($site->domain)
            && strcasecmp((string) $site->domain, $request->getHost()) === 0
            && Route::has('blog.index');
    }
}
