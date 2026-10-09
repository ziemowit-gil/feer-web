<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Services\CrmSync;

class SyncSubscriberToCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $subscriberId, public string $event = 'confirmed', public ?string $szoFormSlug = null) {}

    public function handle(CrmSync $sync): void
    {
        $s = Subscriber::find($this->subscriberId);
        if ($s) {
            $sync->sync($s, $this->event, $this->szoFormSlug);
        }
    }
}
