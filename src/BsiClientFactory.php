<?php

declare(strict_types=1);

namespace Inisiatif\Bsi;

final class BsiClientFactory
{
    public static function makeFromConfig(array $config): BsiClient
    {
        $credentials = new BsiCredentials(
            apiKey: (string) ($config['api_key'] ?? ''),
            custId: (string) ($config['cust_id'] ?? ''),
            userId: (string) ($config['user_id'] ?? ''),
            password: (string) ($config['password'] ?? ''),
            sandboxUrl: (string) ($config['sandbox_url'] ?? ''),
            productionUrl: (string) ($config['production_url'] ?? ''),
            channelId: (string) ($config['channel_id'] ?? 'API'),
            isDevelopment: ($config['env'] ?? 'production') !== 'production',
            verifySsl: ($config['verify_ssl'] ?? true) == true
        );

        return new BsiClient($credentials);
    }
}
