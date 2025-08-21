<?php

namespace App\Providers;

use App\Syllaby;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Pennant\Feature;
use App\Shared\Mixins\StrMixins;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Shared\Newsletters\Newsletter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use App\Syllaby\Animation\Vendors\Minimax;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Analytics\Services\PostHog;
use App\Syllaby\Ideas\Services\KeywordTool;
use Illuminate\Http\Client\RequestException;
use App\Syllaby\Analytics\Contracts\AbTester;
use App\Syllaby\Ideas\Contracts\IdeaDiscovery;
use App\Shared\Newsletters\MailerLiteNewsletter;
use App\Syllaby\Videos\Contracts\ImageModerator;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Videos\Vendors\Moderators\Gemini;
use App\Syllaby\Scraper\Contracts\ScraperContract;
use App\Syllaby\Assets\Contracts\StockImageContract;
use App\Syllaby\Assets\Contracts\StockVideoContract;
use App\Syllaby\Generators\Contracts\ImageGenerator;
use App\Syllaby\Generators\Vendors\Images\Replicate;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Syllaby\Assets\Vendor\StockMedia\PexelsImages;
use App\Syllaby\Assets\Vendor\StockMedia\PexelsVideos;
use App\Syllaby\Folders\Contracts\FolderNameGenerator;
use App\Syllaby\Animation\Contracts\AnimationGenerator;
use App\Syllaby\Publisher\Channels\Extensions\CustomLinkedInProvider;
use App\Syllaby\Folders\Generators\NumberAppendingFolderNameGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        RequestException::dontTruncate();

        $this->app->singleton(AbTester::class, PostHog::class);
        $this->app->singleton(ImageGenerator::class, Replicate::class);
        $this->app->singleton(IdeaDiscovery::class, KeywordTool::class);
        $this->app->singleton(Newsletter::class, MailerLiteNewsletter::class);
        $this->app->singleton(StockImageContract::class, PexelsImages::class);
        $this->app->singleton(StockVideoContract::class, PexelsVideos::class);
        $this->app->singleton(AnimationGenerator::class, Minimax::class);
        $this->app->singleton(FolderNameGenerator::class, NumberAppendingFolderNameGenerator::class);
        $this->app->singleton(ScraperContract::class, fn () => new FireCrawl(Auth::user()));
        $this->app->singleton(ImageModerator::class, Gemini::class);

        Relation::enforceMorphMap([
            'tag' => Syllaby\Tags\Tag::class,
            'user' => Syllaby\Users\User::class,
            'topic' => Syllaby\Ideas\Topic::class,
            'media' => Syllaby\Assets\Media::class,
            'video' => Syllaby\Videos\Video::class,
            'asset' => Syllaby\Assets\Asset::class,
            'voice' => Syllaby\Speeches\Voice::class,
            'folder' => Syllaby\Folders\Folder::class,
            'keyword' => Syllaby\Ideas\Keyword::class,
            'footage' => Syllaby\Videos\Footage::class,
            'speech' => Syllaby\Speeches\Speech::class,
            'avatar' => Syllaby\RealClones\Avatar::class,
            'faceless' => Syllaby\Videos\Faceless::class,
            'clonable' => Syllaby\Clonables\Clonable::class,
            'template' => Syllaby\Templates\Template::class,
            'generator' => Syllaby\Generators\Generator::class,
            'scheduler' => Syllaby\Schedulers\Scheduler::class,
            'character' => Syllaby\Characters\Character::class,
            'real-clone' => Syllaby\RealClones\RealClone::class,
            'occurrence' => Syllaby\Schedulers\Occurrence::class,
            'publication' => Syllaby\Publisher\Publications\Publication::class,
            'social-channel' => Syllaby\Publisher\Channels\SocialChannel::class,
        ]);

        Feature::useMorphMap();

        // Configure Cashier to use our custom Subscription model
        Cashier::useSubscriptionModel(Syllaby\Subscriptions\Subscription::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Model::preventLazyLoading(! app()->isProduction());

        $this->registerLinkedIn();

        $this->registerHttpMacros();

        $this->registerCustomMacros();
    }

    private function registerLinkedIn(): void
    {
        Socialite::extend('linkedin', function ($app) {
            $config = $app['config']['services.linkedin'];

            return new CustomLinkedInProvider($app['request'], $config['client_id'], $config['client_secret'], $config['redirect']);
        });
    }

    private function registerHttpMacros(): void
    {
        Http::macro('meta', function (): PendingRequest {
            return Http::baseUrl(sprintf('https://graph.facebook.com/%s', config('services.facebook.graph_version')))->timeout(120);
        });

        Http::macro('replicate', function (): PendingRequest {
            return Http::baseUrl(config('services.replicate.url'))
                ->withToken(config('services.replicate.key'))
                ->retry(3, 1000)
                ->timeout(140)
                ->asJson();
        });
    }

    private function registerCustomMacros(): void
    {
        Arr::macro('arrow', function ($array): array {
            return collect($array)->dot()
                ->keyBy(fn ($value, $key) => str_replace('.', '->', $key))
                ->all();
        });

        Str::mixin(new StrMixins);
    }
}
