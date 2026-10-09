<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Mail\DoubleOptInMail;

class SendDoubleOptIn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $subscriberId) {}

    public function handle(): void
    {
        $s = Subscriber::find($this->subscriberId);
        if (! $s || ! $s->isPending()) {
            return;
        }

        Mail::to($s->email, $s->name ?: null)->send(new DoubleOptInMail($s));
        $s->forceFill(['confirmation_sent_at' => now()])->saveQuietly();
        $s->logEvent('doi_sent');
    }
}
