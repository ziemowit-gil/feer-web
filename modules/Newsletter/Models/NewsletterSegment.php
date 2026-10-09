<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Newsletter\Services\SegmentResolver;

/** Segment dynamiczny — reguły JSON wyliczane w chwili wysyłki. */
class NewsletterSegment extends Model
{
    protected $table = 'newsletter_segments';

    protected $fillable = ['site_id', 'name', 'rules', 'cached_count', 'counted_at'];

    protected $casts = ['rules' => 'array', 'counted_at' => 'datetime'];

    public function subscribersQuery()
    {
        return app(SegmentResolver::class)->query($this->rules ?? []);
    }

    public function refreshCount(): int
    {
        $count = $this->subscribersQuery()->count();
        $this->forceFill(['cached_count' => $count, 'counted_at' => now()])->save();

        return $count;
    }
}
