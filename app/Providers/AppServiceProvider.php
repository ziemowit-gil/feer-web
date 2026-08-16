<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Event as EventModel;
use App\Models\NavItem;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Observers\BannerObserver;
use App\Observers\EventObserver;
use App\Observers\NewsCategoryObserver;
use App\Observers\NewsObserver;
use App\Observers\PageObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Obsługa zapisu na webinar — domyślnie lokalna. Podmień to wiązanie,
        // aby przekierować zapisy do zewnętrznego systemu (izolacja integracji).
        $this->app->bind(
            \App\Services\Webinar\RegistrationHandler::class,
            \App\Services\Webinar\LocalRegistrationHandler::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('shortcodes', fn ($expr) => "<?php echo \\App\\Support\\ShortcodeParser::render($expr); ?>");

        Banner::observe(BannerObserver::class);
        News::observe(NewsObserver::class);
        NewsCategory::observe(NewsCategoryObserver::class);
        Page::observe(PageObserver::class);
        EventModel::observe(EventObserver::class);

        // Rejestracja providera Microsoft 365 dla Laravel Socialite.
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', Provider::class);
        });

        // Konfiguracja poczty z panelu nadpisuje .env (SMTP). Owinięte w try/catch,
        // bo tabela ustawień może jeszcze nie istnieć (np. podczas instalacji).
        try {
            $settings = SiteSetting::current();
            $this->applySiteUrl($settings);
            $this->applyMailConfig($settings);
        } catch (\Throwable $e) {
            // Brak zmigrowanej bazy / ustawień — zostajemy przy konfiguracji z .env.
        }

        View::composer('partials.header', function ($view) {
            // Only categories that have at least one active (non-completed)
            // project appear in the menu — mirroring the projects list and the
            // category pages, which hide completed projects. Categories with no
            // projects, or only archived ones, are left out entirely.
            $view->with(
                'navCategories',
                Category::with(['publishedProjects' => fn ($query) => $query->where('is_completed', false)])
                    ->orderBy('order')->orderBy('name')->get()
                    ->filter(fn ($category) => $category->publishedProjects->isNotEmpty())
            );

            // Whether any published, completed project exists — used to show the
            // "To już zrobiliśmy" (archive) link in the projects menu.
            $view->with(
                'navHasProjectArchive',
                Project::where('is_published', true)->where('is_completed', true)->exists()
            );

            $view->with(
                'navPages',
                Page::whereNull('parent_id')->whereNull('project_id')->where('is_published', true)->where('show_in_menu', true)->with('publishedChildren')
                    ->orderBy('order')->orderBy('title')->get()
            );

            $siteSettings = SiteSetting::current();

            $view->with(
                'navItems',
                NavItem::where('location', 'main')->whereNull('parent_id')->where('is_active', true)->with('children')
                    ->orderBy('order')->get()
                    ->filter(fn (NavItem $item) => ! $item->module || $siteSettings->isModuleEnabled($item->module))
            );
        });

        View::composer('partials.footer', function ($view) {
            $siteSettings = SiteSetting::current();

            $view->with(
                'footerNavItems',
                NavItem::where('location', 'footer')->where('is_active', true)
                    ->orderBy('order')->get()
                    ->filter(fn (NavItem $item) => ! $item->module || $siteSettings->isModuleEnabled($item->module))
            );
        });

        View::composer('*', fn ($view) => $view->with('siteSettings', SiteSetting::current()));
    }

    /**
     * Nadpisz główny adres strony (config('app.url') oraz root URL generatora
     * linków) wartością ustawioną w panelu. Dzięki temu linki bezwzględne
     * (sitemap, e-maile, powiadomienia) używają adresu z panelu zamiast APP_URL.
     * Puste pole = dziedziczenie z .env, jak dotychczas.
     */
    private function applySiteUrl(SiteSetting $settings): void
    {
        $url = trim((string) $settings->site_url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        $url = rtrim($url, '/');
        config(['app.url' => $url]);
        URL::forceRootUrl($url);

        if (str_starts_with($url, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Nadpisz konfigurację poczty (config/mail) wartościami z panelu, gdy admin
     * skonfigurował własny serwer SMTP. Puste pola dziedziczą z .env.
     */
    private function applyMailConfig(SiteSetting $settings): void
    {
        if (filled($settings->mail_from_address)) {
            config(['mail.from.address' => $settings->mail_from_address]);
        }
        if (filled($settings->mail_from_name)) {
            config(['mail.from.name' => $settings->mail_from_name]);
        }

        // Wbudowana poczta PHP: użyj mailera „sendmail" (pipe do binarki systemowej),
        // bez potrzeby konfigurowania SMTP. Odpowiednik funkcji mail() na hostingu.
        if ($settings->mail_transport === 'sendmail') {
            config(['mail.default' => 'sendmail']);

            return;
        }

        if (! $settings->mailConfigured()) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp'), array_filter([
                'host' => $settings->mail_host,
                'port' => $settings->mail_port,
                'username' => $settings->mail_username,
                'password' => $settings->mail_password,
                'scheme' => $settings->mail_encryption === 'ssl' ? 'smtps' : null,
            ], fn ($value) => filled($value))),
        ]);
    }
}
