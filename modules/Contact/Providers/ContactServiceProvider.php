<?php

declare(strict_types=1);

namespace Modules\Contact\Providers;

use App\PageTypes\PageTypeRegistry;
use Illuminate\Support\ServiceProvider;
use Modules\Contact\Console\ConvertLegacyPagesCommand;
use Modules\Contact\PageTypes\ContactBookPageType;
use Modules\Contact\PageTypes\ContactPageType;

/**
 * Moduł Kontakt — typy stron „Kontakt” i „Kontakt — książka”, automatyczne podstrony kontaktu (/kontakt/…) oraz
 * polecenie przenoszące dawne strony „Książka telefoniczna” na nowy typ.
 *
 * Widoki publicznej strony kontaktu (resources/views/contact/**) zostają wspólne z pozostałymi układami kontaktu
 * (classic, card, split, tabs, feer); moduł dokłada to, co jest jego: typy, trasy podstron, konwersję danych.
 */
final class ContactServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'contact-module');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // „Kontakt” podmienia wbudowany typ o tym samym kluczu (contact), więc istniejące strony działają bez zmian.
        PageTypeRegistry::register(new ContactPageType);
        PageTypeRegistry::register(new ContactBookPageType);

        if ($this->app->runningInConsole()) {
            $this->commands([ConvertLegacyPagesCommand::class]);
        }
    }
}
