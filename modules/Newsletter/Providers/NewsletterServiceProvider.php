<?php

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use App\Models\SiteSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Newsletter\Channels\ChannelRegistry;
use Modules\Newsletter\Channels\EmailChannel;
use Modules\Newsletter\Channels\SmsChannel;
use Modules\Newsletter\Channels\WebPushChannel;
use Modules\Newsletter\Console\DispatchDueCampaigns;
use Modules\Newsletter\Console\ExpirePendingSubscribers;
use Modules\Newsletter\Console\PruneNewsletterData;
use Modules\Newsletter\Console\RunRecurringCampaigns;
use Modules\Newsletter\Console\SyncSubscribersToCrm;
use Modules\Newsletter\View\Components\SignupWidget;

/**
 * Moduł Newsletter — rejestruje trasy, widoki, kanały wysyłki, polecenia
 * konsolowe i harmonogram. Konfiguracja dostawcy poczty newslettera jest
 * wstrzykiwana z SiteSetting do osobnego mailera `newsletter`, żeby poczta
 * transakcyjna (Graph) pozostała nietknięta.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
final class NewsletterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelRegistry::class, function ($app) {
            $registry = new ChannelRegistry();
            $registry->add($app->make(EmailChannel::class));
            $registry->add($app->make(WebPushChannel::class));
            $registry->add($app->make(SmsChannel::class));

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'newsletter');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        Blade::component('newsletter-widget', SignupWidget::class);

        $this->applyMailerConfig();

        if ($this->app->runningInConsole()) {
            $this->commands([
                DispatchDueCampaigns::class,
                RunRecurringCampaigns::class,
                ExpirePendingSubscribers::class,
                PruneNewsletterData::class,
                SyncSubscribersToCrm::class,
            ]);

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                $schedule->command('newsletter:dispatch-due')->everyMinute()->withoutOverlapping();
                $schedule->command('newsletter:recurring')->hourly()->withoutOverlapping();
                $schedule->command('newsletter:expire-pending')->dailyAt('03:10');
                $schedule->command('newsletter:prune')->dailyAt('03:30');
                $schedule->command('newsletter:sync-crm')->everyFifteenMinutes()->withoutOverlapping();
            });
        }
    }

    /**
     * Buduje mailer `newsletter` z ustawień panelu. Puste ustawienie = domyślny
     * mailer aplikacji (MAIL_MAILER), więc moduł działa od razu po włączeniu.
     */
    private function applyMailerConfig(): void
    {
        try {
            $site = SiteSetting::current();
        } catch (\Throwable) {
            return;
        }

        $transport = (string) ($site->newsletter_mailer ?: config('mail.default'));
        $mailer    = config("mail.mailers.{$transport}", ['transport' => $transport]);

        switch ($transport) {
            case 'ses':
                if (filled($site->newsletter_ses_key)) {
                    config([
                        'services.ses.key'    => $site->newsletter_ses_key,
                        'services.ses.secret' => $site->newsletter_ses_secret,
                        'services.ses.region' => $site->newsletter_ses_region ?: 'eu-central-1',
                    ]);
                }
                break;
            case 'mailgun':
                if (filled($site->newsletter_mailgun_secret)) {
                    config([
                        'services.mailgun.domain'   => $site->newsletter_mailgun_domain,
                        'services.mailgun.secret'   => $site->newsletter_mailgun_secret,
                        'services.mailgun.endpoint' => $site->newsletter_mailgun_endpoint ?: 'api.eu.mailgun.net',
                    ]);
                }
                break;
            case 'postmark':
                if (filled($site->newsletter_postmark_token)) {
                    config(['services.postmark.token' => $site->newsletter_postmark_token]);
                }
                break;
            case 'smtp':
                if (filled($site->newsletter_smtp_host)) {
                    $mailer = array_merge($mailer, [
                        'transport'  => 'smtp',
                        'host'       => $site->newsletter_smtp_host,
                        'port'       => (int) ($site->newsletter_smtp_port ?: 587),
                        'username'   => $site->newsletter_smtp_username,
                        'password'   => $site->newsletter_smtp_password,
                        'encryption' => $site->newsletter_smtp_encryption ?: 'tls',
                        'scheme'     => ($site->newsletter_smtp_encryption ?: 'tls') === 'ssl' ? 'smtps' : null,
                    ]);
                }
                break;
        }

        config(['mail.mailers.newsletter' => $mailer]);
    }
}
