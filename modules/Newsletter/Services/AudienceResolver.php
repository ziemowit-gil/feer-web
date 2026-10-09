<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterSegment;
use Modules\Newsletter\Models\NewsletterSuppression;

/**
 * Odbiorcy kampanii: {"lists":[…],"segments":[…],"topics":[…],"all":bool,"exclude_lists":[…]}
 * Zawsze: status = confirmed, bez listy tłumienia.
 */
class AudienceResolver
{
    public function __construct(private readonly SegmentResolver $segments) {}

    public function query(NewsletterCampaign $campaign): Builder
    {
        $a = $campaign->audience ?? [];

        $q = Subscriber::query()->confirmed()->whereNull('anonymized_at');

        if ($campaign->site_id) {
            $q->forSite((int) $campaign->site_id);
        }

        $lists    = array_map('intval', (array) ($a['lists'] ?? []));
        $segments = array_map('intval', (array) ($a['segments'] ?? []));
        $topics   = array_values(array_filter((array) ($a['topics'] ?? [])));
        $all      = (bool) ($a['all'] ?? ($lists === [] && $segments === [] && $topics === []));

        if (! $all) {
            $q->where(function (Builder $w) use ($lists, $segments, $topics) {
                if ($lists !== []) {
                    $w->orWhereHas('lists', fn ($l) => $l->whereIn('newsletter_lists.id', $lists));
                }
                foreach (NewsletterSegment::whereIn('id', $segments)->get() as $segment) {
                    $w->orWhereIn('id', $this->segments->query($segment->rules ?? [])->select('id'));
                }
                foreach ($topics as $topic) {
                    $w->orWhereJsonContains('topics', $topic);
                }
            });
        }

        $exclude = array_map('intval', (array) ($a['exclude_lists'] ?? []));
        if ($exclude !== []) {
            $q->whereDoesntHave('lists', fn ($l) => $l->whereIn('newsletter_lists.id', $exclude));
        }

        $q->whereNotIn('email_hash', NewsletterSuppression::query()
            ->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->select('email_hash'));

        return $q;
    }

    public function count(NewsletterCampaign $campaign): int
    {
        return $this->query($campaign)->count();
    }
}
