<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use App\Syllaby\Subscriptions\Services\GooglePlayApiClient;
use App\Syllaby\Subscriptions\Commands\SyncGooglePlayCommand;
use App\Syllaby\Subscriptions\Services\GooglePlayIdGenerator;
use App\Syllaby\Subscriptions\Services\GooglePlayPlanService;
use App\Syllaby\Subscriptions\Services\GooglePlayPriceValidator;
use App\Syllaby\Subscriptions\Services\GooglePlayProductService;
use App\Syllaby\Subscriptions\Services\GooglePlayVerificationService;
use App\Syllaby\Subscriptions\Services\GooglePlayAcknowledgementService;
use App\Syllaby\Subscriptions\Services\GooglePlayOfferService;
use App\Syllaby\Subscriptions\Services\GooglePlaySubscriptionService;
use App\Syllaby\Subscriptions\Contracts\GooglePlayApiClientInterface;
use App\Syllaby\Subscriptions\Contracts\GooglePlayProductServiceInterface;
use App\Syllaby\Subscriptions\Contracts\GooglePlaySubscriptionServiceInterface;

class GooglePlayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register singletons for shared services
        $this->app->singleton(GooglePlayApiClientInterface::class, GooglePlayApiClient::class);
        $this->app->singleton(GooglePlayPriceValidator::class);
        $this->app->singleton(GooglePlayIdGenerator::class);

        // Register service implementations
        $this->app->singleton(GooglePlayAcknowledgementService::class);
        $this->app->singleton(GooglePlayOfferService::class);
        $this->app->singleton(GooglePlaySubscriptionService::class);
        $this->app->singleton(GooglePlayProductService::class);
        $this->app->singleton(GooglePlayVerificationService::class);

        // Register specialized services with proper dependency injection
        $this->app->bind(GooglePlayProductServiceInterface::class, GooglePlayProductService::class);
        $this->app->bind(GooglePlaySubscriptionServiceInterface::class, GooglePlaySubscriptionService::class);

        // Register main orchestration service
        $this->app->singleton(GooglePlayPlanService::class, function ($app) {
            return new GooglePlayPlanService(
                $app->make(GooglePlayProductServiceInterface::class),
                $app->make(GooglePlaySubscriptionServiceInterface::class)
            );
        });

        // Register commands
        $this->commands([
            SyncGooglePlayCommand::class,
            \App\Syllaby\Subscriptions\Commands\FetchGooglePlayOffersCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/google-play.php' => config_path('google-play.php'),
        ], 'google-play-config');

        // ------------------------------------------------------------------
        // Auto-provision service-account file & RTDN project id from inline
        // credentials_json so the rest of the Google SDKs (Pub/Sub, etc.)
        // can rely on Application Default Credentials without duplicating
        // values in .env.
        // ------------------------------------------------------------------
        $credsJson = config('google-play.credentials_json');
        if ($credsJson) {
            // Persist JSON to a stable location so ADC can pick it up
            $keysDir = storage_path('keys');
            File::ensureDirectoryExists($keysDir);
            $saPath = $keysDir.'/google-play-sa.json';

            // Write only if file is missing or content changed (avoid i/o)
            if (! is_file($saPath) || md5_file($saPath) !== md5($credsJson)) {
                file_put_contents($saPath, $credsJson, LOCK_EX);
            }

            // Expose path for Google SDKs (runtime only – no .env write)
            putenv("GOOGLE_APPLICATION_CREDENTIALS={$saPath}");
            // Some libs read from SERVER/ENV super-globals too
            $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $saPath;
            $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] = $saPath;

            // Inject project_id into config if missing
            $projectId = json_decode($credsJson, true)['project_id'] ?? null;
            if ($projectId && ! config('google-play.rtdn.project_id')) {
                config(['google-play.rtdn.project_id' => $projectId]);
            }
        }
    }
}
