<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

/** Słownik linków kampanii — podstawa mapy cieplnej kliknięć. */
class NewsletterCampaignLink extends Model
{
    protected $table = 'newsletter_campaign_links';

    public $timestamps = false;

    protected $fillable = ['campaign_id', 'hash', 'url', 'label', 'position', 'clicks_total', 'clicks_unique'];

    public static function hashFor(string $url): string
    {
        return substr(\App\Models\Subscriber::hashEmail('link:' . $url), 0, 16);
    }
}
