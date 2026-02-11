<?php

declare(strict_types=1);

namespace Inisiatif\Bsi;

final class BsiCredentials
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $custId,
        public readonly string $userId,
        public readonly string $password,
        public readonly string $sandboxUrl,
        public readonly string $productionUrl,
        public readonly string $channelId = 'API',
        public readonly bool $isDevelopment = true
    ) {}
}
