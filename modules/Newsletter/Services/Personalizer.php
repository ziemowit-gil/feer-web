<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;

/**
 * Tagi personalizacji {{nazwa}} z filtrami |default:…, |upper, |date:format.
 * Wartości pochodzące od użytkownika są escapowane (HTML), żeby nikt nie
 * wstrzyknął znaczników przez formularz zapisu.
 */
class Personalizer
{
    public const TAGS = [
        'first_name' => 'Imię (pierwsze słowo)', 'name' => 'Imię / nazwa', 'email' => 'Adres e-mail',
        'unsubscribe_url' => 'Link wypisu', 'preferences_url' => 'Link do preferencji', 'webversion_url' => 'Zobacz w przeglądarce',
        'site_name' => 'Nazwa witryny', 'site_url' => 'Adres witryny', 'campaign_title' => 'Nazwa kampanii',
        'subject' => 'Temat', 'date' => 'Data (|date:d.m.Y)', 'topic_list' => 'Tematy subskrybenta', 'year' => 'Rok',
    ];

    /** Zamienniki legacy Mosaico: [unsubscribe_link], [show_link], [profile_link]. */
    private const LEGACY = [
        '[unsubscribe_link]' => '{{unsubscribe_url}}',
        '[show_link]'        => '{{webversion_url}}',
        '[profile_link]'     => '{{preferences_url}}',
    ];

    public function variables(Subscriber $s, ?NewsletterCampaign $c = null, ?NewsletterDelivery $d = null): array
    {
        $site = SiteSetting::current();

        return [
            'first_name'      => e($s->firstName()),
            'name'            => e((string) $s->name),
            'email'           => e($s->email),
            'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => $s->token]),
            'preferences_url' => route('newsletter.preferences', ['token' => $s->token]),
            'webversion_url'  => $d ? route('newsletter.web', ['uuid' => $d->uuid]) : ($c ? route('newsletter.web.preview', ['uuid' => $c->uuid]) : '#'),
            'site_name'       => e($site->site_name ?? config('app.name')),
            'site_url'        => rtrim((string) config('app.url'), '/'),
            'campaign_title'  => e((string) ($c?->title ?? '')),
            'subject'         => e((string) ($c?->subject ?? '')),
            'date'            => now()->format('d.m.Y'),
            'year'            => now()->format('Y'),
            'topic_list'      => e(implode(', ', $s->topicLabels())),
        ];
    }

    public function render(string $template, array $vars): string
    {
        $template = strtr($template, self::LEGACY);

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)((?:\s*\|\s*[a-z_]+(?::[^}|]*)?)*)\s*\}\}/i',
            function (array $m) use ($vars): string {
                $key   = strtolower($m[1]);
                $value = $vars[$key] ?? '';

                if ($m[2] !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $m[2]))) as $filter) {
                        [$name, $arg] = array_pad(explode(':', $filter, 2), 2, null);
                        $value = match (strtolower($name)) {
                            'default' => $value === '' ? e((string) $arg) : $value,
                            'upper'   => mb_strtoupper((string) $value),
                            'lower'   => mb_strtolower((string) $value),
                            'date'    => now()->format($arg ?: 'd.m.Y'),
                            default   => $value,
                        };
                    }
                }

                return (string) $value;
            },
            $template
        );
    }

    /** Sprawdza, czy treść zawiera wymagany link wypisu (w dowolnej składni). */
    public static function hasUnsubscribeTag(string $html): bool
    {
        return str_contains($html, '{{unsubscribe_url}}') || str_contains($html, '[unsubscribe_link]') || str_contains($html, '{{ unsubscribe_url }}');
    }
}
