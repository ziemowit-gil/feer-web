<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

/**
 * Pojedyncza decyzja odwiedzającego z banera cookies (akceptacja / odmowa).
 * consent_id to losowy identyfikator z localStorage przeglądarki — pozwala
 * powiązać kolejne zmiany decyzji tej samej osoby bez zbierania danych osobowych.
 */
class CookieConsent extends Model
{
    use BelongsToSite;

    public const UPDATED_AT = null;

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    protected $fillable = ['site_id', 'consent_id', 'decision', 'ip_anon', 'user_agent'];

    /** IPv4: zeruje ostatni oktet, IPv6: zostawia pierwsze 48 bitów. */
    public static function anonymizeIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);

            return inet_ntop(substr($packed, 0, 6).str_repeat("\0", 10));
        }

        return null;
    }
}
