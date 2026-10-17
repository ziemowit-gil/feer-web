<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CmsVersion;
use App\Support\HealthDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu: moduły premium (Działalność, Dokumenty i BIP, Sprawozdania) z niebieską obwódką; wersja CMS i log wdrożenia. */
class AdminPremiumMarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_moduly_premium_maja_obwodke_i_opis(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('tm-head nav-link relative tm-premium', $html);
        $this->assertStringContainsString('title="Działalność — moduł premium"', $html);
        $this->assertStringContainsString('Działalność<span class="sr-only"> (moduł premium)</span>', $html);
        $this->assertStringNotContainsString('title="Pulpit — moduł premium"', $html);
        $this->assertStringContainsString('.tm-head.tm-premium { box-shadow: inset 0 0 0 2px var(--color-brand-dark), inset 0 0 0 4px #fff; }', $html);
        $this->assertStringContainsString('.admin-joomla .tm-head { min-height: 2.5rem; background: var(--color-brand-light); border: 1px solid var(--color-brand-dark);', $html);
        $this->assertStringContainsString('.admin-joomla .tm-group.is-active > .tm-head { background: var(--color-brand-dark);', $html);
    }

    public function test_wersja_cms_i_wynik_wdrozenia_z_logu(): void
    {
        $this->assertSame((string) config('cms.version'), CmsVersion::get());
        config(['cms.version' => null]); // stary cache konfiguracji bez cms.php
        $this->assertMatchesRegularExpression('/^\d+\.\d+/', CmsVersion::get());

        $log = sys_get_temp_dir().'/deploy-20261017-205434.log';
        file_put_contents($log, "# start: ".now()->subMinutes(30)->getTimestamp()."\n\$ git pull\nJuż aktualne.\n\n# wynik: OK | commit: 0d591d2 | czas: 2 min 5 s | koniec: 1\n");
        $r = HealthDashboard::parseDeployLog($log);
        $this->assertSame(['ok', '0d591d2', '2 min 5 s'], [$r['result'], $r['commit'], $r['duration']]);
        $this->assertTrue($r['at']->isPast(), 'Czas z „# start:” nie wypada w przyszłości (strefa serwera ≠ aplikacji).');

        file_put_contents($log, "# start: ".now()->getTimestamp()."\n\n# wynik: NIEUDANE | krok: Pobieranie kodu (git pull) | czas: 12 s | koniec: 1\n");
        $this->assertSame(['fail', 'Pobieranie kodu (git pull)'], [HealthDashboard::parseDeployLog($log)['result'], HealthDashboard::parseDeployLog($log)['failed_step']]);
        @unlink($log);
    }
}
