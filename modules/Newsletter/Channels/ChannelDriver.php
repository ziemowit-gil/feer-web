<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

use App\Models\Subscriber;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;

interface ChannelDriver
{
    /** email | webpush | sms */
    public function key(): string;

    public function label(): string;

    /** Czy kanał jest skonfigurowany i włączony w ustawieniach. */
    public function enabled(): bool;

    /** Czy ten subskrybent może dostać wiadomość tym kanałem (zgoda + dane). */
    public function supports(Subscriber $subscriber): bool;

    public function render(NewsletterCampaign $campaign, Subscriber $subscriber, NewsletterDelivery $delivery): OutboundMessage;

    public function send(OutboundMessage $message, NewsletterDelivery $delivery): DeliveryResult;

    public function maxPerMinute(): int;
}
