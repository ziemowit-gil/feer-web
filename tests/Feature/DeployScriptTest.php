<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Kreator wdrożenia (deploy.sh): podgląd wyglądu `--demo` i `--demo-blad` działa bez żadnych zmian w systemie
 * (plik tymczasowy zamiast storage/logs), ma poprawne kody wyjścia i pokazuje kroki, podsumowanie oraz ramkę wyniku.
 */
class DeployScriptTest extends TestCase
{
    /** @return array{0: int, 1: string} kod wyjścia i wyjście skryptu */
    private function deploy(string $args): array
    {
        $cmd = 'cd '.escapeshellarg(base_path()).' && NO_COLOR=1 DEMO_PAUSE=0 COLUMNS=100 LANG=en_US.UTF-8 bash deploy.sh '.$args.' 2>&1';
        exec($cmd, $lines, $code);

        return [$code, implode("\n", $lines)];
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! is_executable('/bin/bash') && ! trim((string) shell_exec('command -v bash'))) {
            $this->markTestSkipped('Brak basha.');
        }
    }

    public function test_podglad_wygladu_pokazuje_kroki_podsumowanie_i_ramke_sukcesu(): void
    {
        $logsBefore = glob(storage_path('logs/deploy-*.log')) ?: [];

        [$code, $out] = $this->deploy('--demo');

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('FEER-web', $out);
        $this->assertStringContainsString('Zmiany do wdrożenia', $out);
        foreach (['1/10', '4/10', '10/10', 'Podsumowanie', 'Wdrożenie zakończone'] as $part) {
            $this->assertStringContainsString($part, $out);
        }
        // Podgląd nie zapisuje w storage/logs (stamtąd czyta pulpit „Kondycja serwisu”).
        $this->assertSame($logsBefore, glob(storage_path('logs/deploy-*.log')) ?: []);
    }

    public function test_podglad_bledu_konczy_sie_kodem_1_i_ramka_niepowodzenia(): void
    {
        [$code, $out] = $this->deploy('--demo-blad');

        $this->assertSame(1, $code, $out);
        $this->assertStringContainsString('npm run build nie powiodło się', $out);
        $this->assertStringContainsString('Ostatnie linie wyjścia', $out);
        $this->assertStringContainsString('Wdrożenie NIEUDANE', $out);
        $this->assertStringContainsString('krok 6/10', $out);
        $this->assertStringContainsString('Po poprawce uruchom ./deploy.sh ponownie.', $out);
    }

    public function test_pomoc_opisuje_podglad_i_znaczniki_logu_dla_pulpitu_zostaly(): void
    {
        [$code, $out] = $this->deploy('--help');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('--demo', $out);
        $this->assertStringContainsString('--dry-run', $out);

        // Pulpit „Kondycja serwisu” czyta znaczniki z logu — format musi zostać (HealthDashboard::parseDeployLog).
        $script = (string) file_get_contents(base_path('deploy.sh'));
        $this->assertStringContainsString("'# start: %s\\n'", $script);
        $this->assertStringContainsString('# wynik: OK | commit: %s | czas: %s | koniec: %s', $script);
        $this->assertStringContainsString('# wynik: NIEUDANE | krok: %s | czas: %s | koniec: %s', $script);
    }
}
