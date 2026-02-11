<?php

declare(strict_types=1);

namespace Inisiatif\Bsi\Providers;

use Illuminate\Support\ServiceProvider;
use Inisiatif\Bsi\BsiClient;
use Inisiatif\Bsi\BsiClientFactory;

final class BsiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BsiClient::class, function () {
            $config = config('services.bsi_api', []);
            
            $client = BsiClientFactory::makeFromConfig($config);
            
            if ($this->app->has('log')) {
                $client->setLogger($this->app->make('log'));
            }
            
            return $client;
        });
    }
}
