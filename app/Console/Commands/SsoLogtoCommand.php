<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * php artisan sso:logto — ustawia logowanie jednokrotne Fundacji (Logto, OpenID Connect) w panelu z wiersza poleceń,
 * zamiast klikania w Ustawienia → Logowanie → SSO. Polecenie 2026-11-14: „napisz małe CLI… z cli mogę wygenerować”.
 *
 *   php artisan sso:logto                                   # stan i podpowiedź
 *   php artisan sso:logto --client-id=ID                    # wpisuje dane aplikacji z logto-admin (sekret pyta bez echa)
 *   php artisan sso:logto --create --m2m-id=… --m2m-secret=…  # sam zakłada aplikację w Logto (Management API) i ją wpisuje
 *   php artisan sso:logto --disable                         # wyłącza logowanie SSO
 *
 * --create potrzebuje aplikacji Machine-to-machine w Logto z rolą dostępu do Management API (np. tej, z której korzysta SZO);
 * dane M2M można też podać w .env: LOGTO_M2M_ID, LOGTO_M2M_SECRET. Adres powrotu: {--url albo APP_URL}/auth/sso/callback.
 * Zapisuje witrynę główną (id 1). Sekretów nie wypisuje.
 */
class SsoLogtoCommand extends Command
{
    public const ISSUER = 'https://logto.feer.org.pl/oidc';

    public const RESOURCE = 'https://default.logto.app/api';

    protected $signature = 'sso:logto
                            {--client-id= : App ID aplikacji z panelu Logto}
                            {--client-secret= : App secret (bez tej opcji zapyta, nie pokazując wpisywanego tekstu)}
                            {--create : Załóż aplikację w Logto przez Management API i wpisz jej dane}
                            {--m2m-id= : Aplikacja Machine-to-machine do Management API (albo LOGTO_M2M_ID)}
                            {--m2m-secret= : Jej sekret (albo LOGTO_M2M_SECRET)}
                            {--app-name=Panel strony feer.org.pl (CMS) : Nazwa nowej aplikacji w Logto}
                            {--url= : Adres strony do adresu powrotu (domyślnie APP_URL)}
                            {--issuer='.self::ISSUER.' : Adres dostawcy (issuer)}
                            {--name=logowanie jednokrotne : Napis na przycisku po „Zaloguj się przez”}
                            {--only : Włącz też „Tylko SSO” (bez formularza hasła na stronie logowania)}
                            {--disable : Wyłącz logowanie SSO}
                            {--dry-run : Pokaż, co zostanie zrobione, bez zapisu i bez zakładania aplikacji}';

    protected $description = 'Logowanie jednokrotne Fundacji (Logto) w panelu: zakłada aplikację w Logto albo wpisuje jej dane';

    public function handle(): int
    {
        $settings = SiteSetting::query()->firstOrCreate(['id' => 1]);
        $issuer = rtrim((string) $this->option('issuer'), '/');
        $base = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        $redirect = $base.'/auth/sso/callback';
        $dry = (bool) $this->option('dry-run');

        if ($this->option('disable')) {
            if (! $dry) {
                $settings->update(['sso_enabled' => false, 'sso_only_login' => false]);
            }
            $this->info($dry ? 'Wyłączyłbym logowanie SSO.' : 'Logowanie SSO wyłączone.');

            return self::SUCCESS;
        }

        $clientId = trim((string) $this->option('client-id'));
        $secret = (string) $this->option('client-secret');

        if ($this->option('create')) {
            $m2mId = (string) ($this->option('m2m-id') ?: env('LOGTO_M2M_ID', ''));
            $m2mSecret = (string) ($this->option('m2m-secret') ?: env('LOGTO_M2M_SECRET', ''));
            if ($m2mId === '' || $m2mSecret === '') {
                $this->error('Do --create podaj --m2m-id i --m2m-secret (albo LOGTO_M2M_ID i LOGTO_M2M_SECRET w .env).');

                return self::FAILURE;
            }
            $this->line("Zakładam w Logto aplikację „{$this->option('app-name')}” z adresem powrotu {$redirect}");
            if ($dry) {
                $this->info('Podgląd: nic nie założono i nie zapisano.');

                return self::SUCCESS;
            }
            try {
                [$clientId, $secret] = $this->createApp(preg_replace('#/oidc$#', '', $issuer), $m2mId, $m2mSecret, (string) $this->option('app-name'), $redirect, $base);
            } catch (Throwable $e) {
                $this->error('Nie udało się założyć aplikacji: '.$e->getMessage());

                return self::FAILURE;
            }
            $this->info("Aplikacja założona, App ID: {$clientId}");
        }

        if ($clientId === '') {
            $this->status($settings, $redirect);

            return self::SUCCESS;
        }
        if ($secret === '') {
            $secret = (string) $this->secret('App secret z panelu Logto');
        }
        if ($secret === '') {
            $this->error('Brak App secret, nic nie zapisano.');

            return self::FAILURE;
        }

        $data = ['sso_enabled' => true, 'sso_name' => (string) $this->option('name'), 'sso_issuer' => $issuer,
            'sso_client_id' => $clientId, 'sso_client_secret' => $secret, 'sso_scopes' => 'openid email profile'];
        if ($this->option('only')) {
            $data['sso_only_login'] = true;
        }
        if ($dry) {
            $this->info('Podgląd: zapisałbym logowanie SSO z App ID '.$clientId.' i issuer '.$issuer.'.');

            return self::SUCCESS;
        }
        $settings->update($data);
        \Illuminate\Support\Facades\Cache::forget('sso-discovery:'.md5($issuer.'/.well-known/openid-configuration'));

        $this->info('Zapisano. Na stronie logowania jest teraz „Zaloguj się przez '.$data['sso_name'].'”.');
        $this->line("W aplikacji w Logto musi być adres powrotu: {$redirect}");

        return self::SUCCESS;
    }

    /** Zakłada aplikację „Traditional web” w Logto. @return array{0:string,1:string} [App ID, App secret] */
    private function createApp(string $logto, string $m2mId, string $m2mSecret, string $name, string $redirect, string $base): array
    {
        $tok = Http::asForm()->withBasicAuth($m2mId, $m2mSecret)->acceptJson()->timeout(15)
            ->post($logto.'/oidc/token', ['grant_type' => 'client_credentials', 'resource' => self::RESOURCE, 'scope' => 'all']);
        if (! $tok->successful() || ! $tok->json('access_token')) {
            throw new RuntimeException('token Management API: HTTP '.$tok->status());
        }
        $app = Http::withToken((string) $tok->json('access_token'))->acceptJson()->timeout(15)->post($logto.'/api/applications', [
            'name' => $name,
            'type' => 'Traditional',
            'description' => 'Logowanie do panelu CMS '.$base.' (php artisan sso:logto)',
            'oidcClientMetadata' => ['redirectUris' => [$redirect], 'postLogoutRedirectUris' => [$base]],
        ]);
        if (! $app->successful() || ! $app->json('id') || ! $app->json('secret')) {
            throw new RuntimeException('zakładanie aplikacji: HTTP '.$app->status());
        }

        return [(string) $app->json('id'), (string) $app->json('secret')];
    }

    private function status(SiteSetting $s, string $redirect): void
    {
        $this->line('Logowanie SSO: '.($s->ssoLoginEnabled() ? 'włączone' : 'wyłączone')
            .($s->sso_issuer ? ", issuer {$s->sso_issuer}" : '').($s->sso_client_id ? ", App ID {$s->sso_client_id}" : ''));
        $this->newLine();
        $this->line('Jak włączyć logowanie jednokrotne Fundacji:');
        $this->line("  1) w logto-admin.feer.org.pl dodaj aplikację „Traditional web” z adresem powrotu {$redirect}");
        $this->line('     i uruchom: php artisan sso:logto --client-id=<App ID>   (o App secret zapyta)');
        $this->line('  2) albo niech polecenie samo ją założy:');
        $this->line('     php artisan sso:logto --create --m2m-id=<ID aplikacji M2M> --m2m-secret=<sekret>');
    }
}
