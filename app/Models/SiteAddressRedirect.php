<?php

namespace App\Models;

use App\Http\Middleware\RedirectPortalSubdomain;
use App\Http\Middleware\ResolveCurrentSite;
use App\Support\SiteAddresses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Dawny adres subwitryny — adres skrócony albo własna domena sprzed zmiany — który przekierowuje (301) na jej obecny
 * adres, żeby po zmianie nazwy i adresu stare linki (menu, newslettery, wyniki wyszukiwarek) nie kończyły się 404.
 *
 * Wiersze powstają same przy zapisie witryny (SiteSetting::updated → rememberChanges) i znikają, gdy ktoś znów zajmie
 * ten adres (releaseAddressesOf) albo witryna zostanie usunięta (forgetSite). Docelowy adres nie jest zapisany: liczymy
 * go z obecnych danych witryny (current_site_url), więc kolejne zmiany nie tworzą łańcuchów przekierowań.
 *
 * Kod trafia na serwer przed migracją, więc każde odwołanie do tabeli sprawdza, czy ona istnieje.
 */
class SiteAddressRedirect extends Model
{
    public const TYPE_SLUG = 'slug';

    public const TYPE_DOMAIN = 'domain';

    /** Klucz mapy adresów w pamięci podręcznej (jak SLUG_MAP_CACHE_KEY — czyszczona przy każdej zmianie). */
    public const CACHE_KEY = 'site_address_redirect_map';

    protected $fillable = ['target_site_id', 'type', 'value'];

    protected $casts = ['target_site_id' => 'integer'];

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SiteSetting::class, 'target_site_id');
    }

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('site_address_redirects');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Wszystkie dawne adresy: typ → (adres małymi literami → id witryny). Mapa siedzi w pamięci podręcznej,
     * bo ResolveCurrentSite pyta o nią przy każdym żądaniu; brak tabeli (przed migracją) to pusta mapa.
     *
     * @return array{slug: array<string, int>, domain: array<string, int>}
     */
    public static function map(): array
    {
        $empty = [self::TYPE_SLUG => [], self::TYPE_DOMAIN => []];

        try {
            return Cache::remember(self::CACHE_KEY, 300, function () use ($empty) {
                if (! static::tableExists()) {
                    return $empty;
                }

                $map = $empty;
                foreach (static::query()->get(['type', 'value', 'target_site_id']) as $row) {
                    $map[$row->type][strtolower((string) $row->value)] = (int) $row->target_site_id;
                }

                return $map;
            });
        } catch (Throwable) {
            return $empty;
        }
    }

    /** Id witryny, na którą przekierowuje dawny adres (null, gdy to nie jest dawny adres żadnej witryny). */
    public static function siteIdFor(string $type, string $value): ?int
    {
        $value = SiteAddresses::normalize($value);

        return $value === null ? null : (self::map()[$type][$value] ?? null);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Dawne adresy witryny, od najnowszego. @return Collection<int, self> */
    public static function forSite(SiteSetting $site): Collection
    {
        try {
            if (! static::tableExists()) {
                return new Collection;
            }

            return static::query()->where('target_site_id', $site->getKey())->orderByDesc('created_at')->orderByDesc('id')->get();
        } catch (Throwable) {
            return new Collection;
        }
    }

    /**
     * Przekierowania innych witryn spod adresów, które witryna (albo nowa — bez id) właśnie zajmuje; zajęcie adresu
     * kończy takie przekierowanie, więc panel mówi o tym wprost.
     *
     * @return Collection<int, self>
     */
    public static function heldByOthers(?int $exceptSiteId, ?string $slug, ?string $domain): Collection
    {
        $wanted = array_filter([
            self::TYPE_SLUG => SiteAddresses::normalize($slug),
            self::TYPE_DOMAIN => SiteAddresses::normalize($domain),
        ]);

        try {
            if ($wanted === [] || ! static::tableExists()) {
                return new Collection;
            }

            return static::query()->with('site')
                ->when($exceptSiteId !== null, fn ($query) => $query->where('target_site_id', '!=', $exceptSiteId))
                ->where(function ($query) use ($wanted) {
                    foreach ($wanted as $type => $value) {
                        $query->orWhere(fn ($pair) => $pair->where('type', $type)->where('value', $value));
                    }
                })
                ->get();
        } catch (Throwable) {
            return new Collection;
        }
    }

    /**
     * Po zmianie adresu subwitryny zapamiętuje dotychczasowy (nowy adres działa od razu, stary przekierowuje).
     * Adres witryny głównej to adres serwera, więc jej to nie dotyczy; adresu skróconego, pod którym działa na stałe
     * trasa (np. /bip, gdy BIP nie jest już na tym adresie), nie zapamiętujemy — wraca do swojej trasy.
     */
    public static function rememberChanges(SiteSetting $site): void
    {
        if ($site->parent_site_id === null || ! $site->wasChanged([self::TYPE_SLUG, self::TYPE_DOMAIN])) {
            return;
        }

        try {
            if (! static::tableExists()) {
                return;
            }

            foreach ([self::TYPE_SLUG, self::TYPE_DOMAIN] as $type) {
                if (! $site->wasChanged($type)) {
                    continue;
                }

                $old = SiteAddresses::normalize($site->getOriginal($type));
                if ($old === null || $old === SiteAddresses::normalize($site->getAttribute($type))) {
                    continue;
                }
                if ($type === self::TYPE_SLUG && SiteAddresses::isReservedSlug($old)) {
                    continue;
                }

                static::updateOrCreate(['type' => $type, 'value' => $old], ['target_site_id' => $site->getKey()]);
            }

            static::flush();
        } catch (Throwable $e) {
            // Zapis witryny nie może się wywrócić na przekierowaniu; brakujące przekierowanie to tylko martwy stary link.
            report($e);
        }
    }

    /**
     * Adres, który witryna właśnie zajęła (nowa albo ze zmienionym adresem), przestaje być czyimkolwiek dawnym adresem —
     * żywy adres wygrywa. Wołać tylko tuż po takim zapisie (SiteSetting::created / updated).
     */
    public static function releaseAddressesOf(SiteSetting $site): void
    {
        $taken = array_filter([
            self::TYPE_SLUG => SiteAddresses::normalize($site->getAttribute(self::TYPE_SLUG)),
            self::TYPE_DOMAIN => SiteAddresses::normalize($site->getAttribute(self::TYPE_DOMAIN)),
        ]);

        if ($taken === []) {
            return;
        }

        try {
            if (! static::tableExists()) {
                return;
            }

            foreach ($taken as $type => $value) {
                static::query()->where('type', $type)->where('value', $value)->delete();
            }

            static::flush();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Usuwa przekierowania na witrynę, której już nie ma. */
    public static function forgetSite(SiteSetting $site): void
    {
        try {
            if (! static::tableExists()) {
                return;
            }

            static::query()->where('target_site_id', $site->getKey())->delete();
            static::flush();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Dlaczego dawny adres nie działa teraz jako przekierowanie (null = działa): pod tym adresem jest już coś żywego,
     * a żywy adres wygrywa. Wspólne dla ResolveCurrentSite (wtedy nie przekierowuje) i panelu (mówi o tym wprost).
     */
    public static function shadowReason(string $type, string $value): ?string
    {
        $value = strtolower($value);

        if ($type === self::TYPE_DOMAIN) {
            $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

            if ($appHost !== '' && $value === $appHost) {
                return 'to adres serwera';
            }

            return app(RedirectPortalSubdomain::class)->isPortalHost($value) ? 'pod tym adresem działa portal kategorii' : null;
        }

        if (in_array($value, ResolveCurrentSite::neverPrefix(), true)) {
            return 'pod tym adresem działa panel albo inna funkcja serwisu';
        }

        return Page::query()->where('slug', $value)->exists() ? 'pod tym adresem jest strona' : null;
    }

    /** @see self::shadowReason() */
    public function blockedReason(): ?string
    {
        return static::shadowReason((string) $this->type, (string) $this->value);
    }

    /** Pełny adres dawnego adresu, np. https://feer.org.pl/szkolenia albo https://szkolenia.przyklad.pl. */
    public function url(): string
    {
        return $this->type === self::TYPE_DOMAIN
            ? 'https://'.$this->value
            : rtrim((string) config('app.url'), '/').'/'.$this->value;
    }

    /** Adres bez schematu, do pokazania w panelu. */
    public function displayAddress(): string
    {
        return (string) preg_replace('#^https?://#', '', $this->url());
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_DOMAIN ? 'Domena' : 'Adres skrócony';
    }
}
