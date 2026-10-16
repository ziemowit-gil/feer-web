<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Carbon;

/**
 * Okno wysyłki newslettera: dni tygodnia (1 = pon … 7 = niedz) i godziny od–do
 * w strefie aplikacji. Poza oknem SendDelivery odkłada dostawę do najbliższego otwarcia.
 * Zapis w site_settings.newsletter_send_window (JSON, bez rzutowania w modelu).
 */
class SendWindow
{
    public const DAYS = [1 => 'pon', 2 => 'wt', 3 => 'śr', 4 => 'czw', 5 => 'pt', 6 => 'sob', 7 => 'niedz'];

    public const DEFAULTS = ['enabled' => false, 'days' => [1, 2, 3, 4, 5], 'from' => '08:00', 'to' => '18:00'];

    public function __construct(public readonly array $config) {}

    public static function fromSettings(?SiteSetting $site = null): self
    {
        $site ??= SiteSetting::current();
        $raw = $site->getAttributes()['newsletter_send_window'] ?? null;
        $cfg = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);

        return new self(self::normalize($cfg));
    }

    public static function normalize(array $cfg): array
    {
        $time = fn ($v, $d) => is_string($v) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : $d;
        $days = array_values(array_unique(array_filter(array_map('intval', (array) ($cfg['days'] ?? self::DEFAULTS['days'])), fn ($d) => $d >= 1 && $d <= 7)));
        sort($days);

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? false),
            'days'    => $days ?: self::DEFAULTS['days'],
            'from'    => $time($cfg['from'] ?? null, self::DEFAULTS['from']),
            'to'      => $time($cfg['to'] ?? null, self::DEFAULTS['to']),
        ];
    }

    public function enabled(): bool
    {
        return $this->config['enabled'];
    }

    public function isOpen(?Carbon $at = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        $at ??= now();
        if (! in_array($at->isoWeekday(), $this->config['days'], true)) {
            return false;
        }
        $t = $at->format('H:i');

        return $this->config['from'] <= $this->config['to']
            ? $t >= $this->config['from'] && $t < $this->config['to']
            : $t >= $this->config['from'] || $t < $this->config['to']; // okno przez północ
    }

    /** Najbliższy moment otwarcia okna (null = otwarte teraz albo okno wyłączone). */
    public function nextOpening(?Carbon $at = null): ?Carbon
    {
        $at ??= now();
        if ($this->isOpen($at)) {
            return null;
        }
        [$h, $m] = array_map('intval', explode(':', $this->config['from']));
        for ($i = 0; $i <= 8; $i++) {
            $candidate = $at->copy()->startOfDay()->addDays($i)->setTime($h, $m);
            if ($candidate->gt($at) && $this->isOpen($candidate)) {
                return $candidate;
            }
        }

        return $at->copy()->addHour();
    }

    public function describe(): string
    {
        if (! $this->enabled()) {
            return 'bez ograniczeń';
        }

        return implode(', ', array_map(fn ($d) => self::DAYS[$d], $this->config['days'])) . ', ' . $this->config['from'] . '–' . $this->config['to'];
    }
}
