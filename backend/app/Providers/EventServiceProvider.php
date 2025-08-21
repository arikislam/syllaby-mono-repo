<?php

namespace App\Providers;

use App\Syllaby;
use Illuminate\Auth\Events\Registered;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\TikTok\TikTokExtendSocialite;
use SocialiteProviders\YouTube\YouTubeExtendSocialite;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use App\Syllaby\Publisher\Channels\Extensions\CustomThreadsExtendSocialite;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            Syllaby\Subscriptions\Listeners\SubscribeNewsletterListener::class,
            Syllaby\Folders\Listeners\CreateRootFolderListener::class,
        ],
        Syllaby\Ideas\Events\KeywordSearched::class => [
            Syllaby\Ideas\Listeners\LogKeywordSearchListener::class,
        ],
        Syllaby\Surveys\Events\BusinessIntakeFormAnswered::class => [
            Syllaby\Surveys\Listeners\AssociateUserToIndustry::class,
        ],
        Syllaby\Videos\Events\FacelessGenerationFailed::class => [
            Syllaby\Videos\Listeners\HandleFailedFacelessGeneration::class,
        ],
        Syllaby\Animation\Events\AnimationGenerationFailed::class => [
            Syllaby\Animation\Listeners\HandleFailedAnimationGeneration::class,
        ],
        MediaHasBeenAddedEvent::class => [
            Syllaby\Assets\Listeners\StoreMediaMetadata::class,
            Syllaby\Assets\Listeners\UpdateAssetOrientation::class,
        ],
        Syllaby\Credits\Events\CreditsRefunded::class => [
            Syllaby\Credits\Listeners\SendCreditsRefundNotification::class,
        ],
        Syllaby\Videos\Events\VideoModified::class => [
            Syllaby\Videos\Listeners\HandleVideoModification::class,
        ],
        SocialiteWasCalled::class => [
            TikTokExtendSocialite::class.'@handle',
            YouTubeExtendSocialite::class.'@handle',
            CustomThreadsExtendSocialite::class.'@handle',
        ],
        Syllaby\Characters\Events\CharacterGenerationFailed::class => [
            Syllaby\Characters\Listeners\RefundAndNotifyUser::class,
        ],
    ];

    protected $observers = [
        Syllaby\Videos\Video::class => [Syllaby\Videos\Observers\VideoObserver::class],
        Syllaby\RealClones\RealClone::class => [Syllaby\RealClones\Observers\RealCloneObserver::class],
    ];

    public function boot(): void
    {
        //
    }
}
