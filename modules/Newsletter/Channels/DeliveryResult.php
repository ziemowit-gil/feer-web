<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

final class DeliveryResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $permanent = false,
        public readonly string $provider = '',
    ) {}

    public static function success(string $provider, ?string $id = null): self
    {
        return new self(true, $id, provider: $provider);
    }

    public static function failure(string $provider, string $message, ?string $code = null, bool $permanent = false): self
    {
        return new self(false, null, $code, mb_substr($message, 0, 500), $permanent, $provider);
    }
}
