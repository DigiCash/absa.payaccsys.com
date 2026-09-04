<?php

namespace App\Providers;

use App\DTOs\StatementsAPI\Support\StatementsApiClientInterface;
use App\DTOs\StatementsAPI\Transport\StatementsApiClient;
use App\DTOs\StatementsAPI\Transport\StatementsApiClientConfig;
use App\Services\StatementsAPI\Contracts\OAuth2TokenManagerInterface;
use App\Services\StatementsAPI\OAuth2TokenManager;
use App\Services\StatementsAPI\StatementService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Transport — concrete client built from `config('absa.statements')`.
        $this->app->bind(StatementsApiClientInterface::class, function (): StatementsApiClient {
            return new StatementsApiClient(StatementsApiClientConfig::fromConfig());
        });

        // Credentials — OAuth2 Client Credentials with the static-key fallback.
        $this->app->bind(OAuth2TokenManagerInterface::class, function (): OAuth2TokenManager {
            return OAuth2TokenManager::fromConfig();
        });

        // Application service — composes the client + credential manager.
        $this->app->singleton(StatementService::class, function (Application $app): StatementService {
            return new StatementService(
                $app->make(StatementsApiClientInterface::class),
                $app->make(OAuth2TokenManagerInterface::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
