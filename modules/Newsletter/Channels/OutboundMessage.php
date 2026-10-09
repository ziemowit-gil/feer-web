<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

/** Wiadomość gotowa do wysłania danym kanałem. */
final class OutboundMessage
{
    public function __construct(
        public readonly string $channel,
        public readonly string $subject,
        public readonly string $html = '',
        public readonly string $text = '',
        public readonly string $url = '',
        public readonly array $headers = [],
    ) {}
}
