<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Lista statyczna subskrybentów (np. „Uczestnicy konferencji 2026"). */
class NewsletterList extends Model
{
    protected $table = 'newsletter_lists';

    protected $fillable = ['site_id', 'name', 'slug', 'description', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'newsletter_list_subscriber', 'list_id', 'subscriber_id')
            ->withPivot(['added_at', 'added_by']);
    }

    public function activeCount(): int
    {
        return $this->subscribers()->where('status', Subscriber::STATUS_CONFIRMED)->count();
    }
}
