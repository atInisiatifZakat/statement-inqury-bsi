<?php

declare(strict_types=1);

namespace Inisiatif\Bsi;

final readonly class BsiCredentials
{
    public function __construct(
        public string $apiKey,
        public string $custId,
        public string $userId,
        public string $password,
        public string $sandboxUrl,
        public string $productionUrl,
        public string $channelId = 'API',
        public bool $isDevelopment = true
    ) {}
}
