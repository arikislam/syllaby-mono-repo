<?php

use App\Http\Controllers\Api\v1;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json('Welcome to Syllaby API');
});

Route::prefix('authentication')->group(function () {
    Route::post('/login', [v1\Authentication\AuthenticationController::class, 'login']);
    Route::post('/register', [v1\Authentication\AuthenticationController::class, 'register']);
    Route::post('/logout', [v1\Authentication\AuthenticationController::class, 'logout']);

    Route::post('/jvzoo/activation', [v1\Authentication\JVZooActivationController::class, 'store']);
    Route::get('/redirect/{provider}', [v1\Authentication\SocialRedirectController::class, 'show']);
    Route::get('/callback/{provider}', [v1\Authentication\SocialCallbackController::class, 'create']);
});

Route::prefix('recovery')->group(function () {
    Route::post('/forgot', [v1\Authentication\PasswordRecoveryController::class, 'forgetPassword']);
    Route::post('/reset', [v1\Authentication\PasswordRecoveryController::class, 'resetPassword']);
});

Route::prefix('notifications')->group(function () {
    Route::get('/', [v1\Notifications\NotificationController::class, 'index']);
    Route::put('/', [v1\Notifications\NotificationController::class, 'update']);
    Route::put('/settings', [v1\Notifications\NotificationSettingsController::class, 'update']);
});

Route::prefix('credits')->group(function () {
    Route::get('overview', [v1\Credits\CreditOverviewController::class, 'index']);
    Route::post('estimate', [v1\Credits\CreditEstimationController::class, 'store']);
});

Route::prefix('surveys')->group(function () {
    Route::get('/', [v1\Surveys\SurveyUserExperienceController::class, 'index']);
    Route::post('/', [v1\Surveys\SurveyUserExperienceController::class, 'store']);
    Route::get('/industries', [v1\Surveys\IndustryController::class, 'index']);
});

Route::prefix('assets')->group(function () {
    Route::post('/upload', [v1\Assets\UploadAssetController::class, 'store']);
    Route::put('/sort', [v1\Assets\SortAssetController::class, 'update']);
    Route::get('/audios', [v1\Assets\AudioController::class, 'index']);
    Route::post('/audios', [v1\Assets\AudioController::class, 'store']);
    Route::post('/watermark', [v1\Assets\WatermarkController::class, 'store']);

    Route::get('/', [v1\Assets\AssetController::class, 'index']);
    Route::get('/{asset}', [v1\Assets\AssetController::class, 'show']);
    Route::patch('/{asset}', [v1\Assets\AssetController::class, 'update']);
    Route::delete('/bulk', [v1\Assets\AssetController::class, 'bulkDestroy']);
    Route::delete('/{asset}', [v1\Assets\AssetController::class, 'destroy']);

    Route::put('/{asset}/bookmark/toggle', [v1\Assets\BookmarkAssetController::class, 'update']);
});

Route::prefix('editor')->group(function () {
    Route::get('/assets', [v1\Editor\EditorAssetController::class, 'index']);
});

Route::prefix('user')->group(function () {
    Route::get('/me', [v1\User\UserProfileController::class, 'show']);
    Route::put('/seen-welcome-message', [v1\User\UserProfileController::class, 'seenWelcomeMessage']);
    Route::put('/password', [v1\User\UserPasswordController::class, 'update']);
    Route::patch('/profile', [v1\User\UserProfileController::class, 'update']);
    Route::get('/credit-history', [v1\User\CreditHistoryController::class, 'index']);
    Route::get('/storage', [v1\User\UserStorageController::class, 'show']);
    Route::get('/publications-usage', [v1\User\UserPublicationUsageController::class, 'show']);
    Route::delete('/', [v1\User\UserProfileController::class, 'destroy']);
});

Route::prefix('folders')->group(function () {
    Route::get('/', [v1\Folders\FolderController::class, 'index']);
    Route::post('/', [v1\Folders\FolderController::class, 'store']);
    Route::put('/{folder}', [v1\Folders\FolderController::class, 'update']);
    Route::put('/{folder}/bookmark', [v1\Folders\BookmarkFolderController::class, 'update']);
    Route::get('/resources', [v1\Folders\ResourceController::class, 'index']);
    Route::put('/resources/{destination}/move', [v1\Folders\ResourceController::class, 'move']);
    Route::delete('/resources', [v1\Folders\ResourceController::class, 'destroy']);
});

Route::prefix('events')->group(function () {
    Route::get('/', [v1\Events\EventController::class, 'index']);
    Route::patch('/{event}', [v1\Events\EventController::class, 'update']);
    Route::put('/{event}/completes', [v1\Events\EventCompleteController::class, 'update']);
    Route::delete('/{event}', [v1\Events\EventController::class, 'destroy']);
    Route::get('/tracker-reports', [v1\Events\TrackerReportController::class, 'show']);
});

Route::prefix('generators')->group(function () {
    Route::get('/options', [v1\Generators\GeneratorOptionController::class, 'index']);
});

Route::prefix('schedulers')->group(function () {
    Route::get('/', [v1\Schedulers\SchedulerController::class, 'index']);
    Route::post('/', [v1\Schedulers\SchedulerController::class, 'store']);
    Route::post('/csv-parser', [v1\Schedulers\CsvParserController::class, 'store']);
    Route::get('/{scheduler}', [v1\Schedulers\SchedulerController::class, 'show']);
    Route::patch('/{scheduler}', [v1\Schedulers\SchedulerController::class, 'update']);

    Route::post('/{scheduler}/run', [v1\Schedulers\RunSchedulerController::class, 'update']);
    Route::put('/{scheduler}/toggle', [v1\Schedulers\ToggleSchedulerController::class, 'update']);

    Route::get('/{scheduler}/occurrences', [v1\Schedulers\OccurrenceController::class, 'index']);
    Route::post('/{scheduler}/occurrences', [v1\Schedulers\OccurrenceController::class, 'store']);
    Route::patch('/occurrences/{occurrence}', [v1\Schedulers\OccurrenceController::class, 'update']);
    Route::put('/occurrences/{occurrence}/scripts', [v1\Schedulers\OccurrenceScriptController::class, 'update']);

    Route::delete('/{scheduler}/events', [v1\Schedulers\SchedulerEventController::class, 'destroy']);
});

Route::prefix('publications')->group(function () {
    Route::get('/limits', [v1\Publication\PublicationLimitController::class, 'index']);

    Route::put('/{publication}/thumbnails/attach', [v1\Publication\AttachThumbnailController::class, 'update']);
    Route::post('/{publication}/thumbnail', [v1\Publication\PublicationThumbnailController::class, 'store']);
    Route::delete('/{publication}/thumbnail', [v1\Publication\PublicationThumbnailController::class, 'destroy']);

    Route::get('/limits', [v1\Publication\PublicationLimitController::class, 'index']);
    Route::get('/{publication}/channels/{channel}', [v1\Publication\PublicationChannelController::class, 'show']);
});

Route::apiResource('publications', v1\Publication\PublicationController::class);

Route::prefix('keywords')->group(function () {
    Route::get('/history', [v1\Ideas\KeywordHistoryController::class, 'index']);
    Route::delete('/{keyword}/history', [v1\Ideas\KeywordHistoryController::class, 'destroy']);
});

Route::prefix('ideas')->group(function () {
    Route::get('/', [v1\Ideas\IdeaController::class, 'index']);
    Route::post('/discover', [v1\Ideas\IdeaDiscoverController::class, 'store']);
    Route::get('/suggestions', [v1\Ideas\IdeaSuggestionController::class, 'index']);
});

Route::prefix('topics')->group(function () {
    Route::get('/', [v1\Ideas\RelatedTopicController::class, 'index']);
    Route::post('/related', [v1\Ideas\RelatedTopicController::class, 'store']);
    Route::put('/{topic}/bookmark', [v1\Ideas\BookmarkTopicController::class, 'update']);
});

Route::prefix('templates')->group(function () {
    Route::get('/', [v1\Templates\TemplateController::class, 'index']);
    Route::get('/{template}', [v1\Templates\TemplateController::class, 'show']);
});

Route::prefix('tags')->group(function () {
    Route::get('/', [v1\Tags\TagController::class, 'index']);
    Route::get('/{tag}', [v1\Tags\TagController::class, 'show']);
});

Route::prefix('presets')->group(function () {
    Route::get('/faceless', [v1\Presets\FacelessPresetController::class, 'index']);
    Route::post('/faceless', [v1\Presets\FacelessPresetController::class, 'store']);
    Route::patch('/faceless/{preset}', [v1\Presets\FacelessPresetController::class, 'update']);
    Route::delete('/faceless/{preset}', [v1\Presets\FacelessPresetController::class, 'destroy']);
});

Route::prefix('characters')->group(function () {
    Route::get('/', [v1\Characters\CharacterController::class, 'index']);
    Route::post('/', [v1\Characters\CharacterController::class, 'store']);
    Route::get('/{character}', [v1\Characters\CharacterController::class, 'show']);
    Route::put('/{character}', [v1\Characters\CharacterController::class, 'update']);
    Route::delete('/{character}', [v1\Characters\CharacterController::class, 'destroy']);
    Route::post('/{character}/image', [v1\Characters\CharacterImageController::class, 'update']);
    Route::post('/{character}/preview', [v1\Characters\CharacterPreviewController::class, 'store']);
    Route::post('/{character}/train', [v1\Characters\CharacterTrainingController::class, 'store']);
});

Route::prefix('videos')->group(function () {
    Route::get('/', [v1\Videos\VideoController::class, 'index']);
    Route::get('/{video}', [v1\Videos\VideoController::class, 'show']);
    Route::patch('/{video}', [v1\Videos\VideoController::class, 'update']);
    Route::delete('/{video}', [v1\Videos\VideoController::class, 'destroy']);
    Route::get('/{uuid}/render', [v1\Videos\VideoRenderController::class, 'show']);
    Route::get('/{video}/status', [v1\Videos\VideoStatusController::class, 'show']);

    Route::prefix('footage')->group(function () {
        Route::post('/', [v1\Videos\FootageController::class, 'store']);
        Route::patch('/{footage}', [v1\Videos\FootageController::class, 'update']);
        Route::put('/{footage}/preference', [v1\Videos\FootagePreferencesController::class, 'update']);
        Route::post('/{footage}/render', [v1\Videos\RenderFootageController::class, 'store']);
    });

    Route::prefix('footage/{footage}/timeline')->group(function () {
        Route::get('/', [v1\Videos\TimelineController::class, 'index']);
        Route::put('/', [v1\Videos\TimelineController::class, 'update']);
    });

    Route::prefix('faceless')->group(function () {
        Route::post('/', [v1\Videos\FacelessController::class, 'store']);
        Route::get('/{faceless}', [v1\Videos\FacelessController::class, 'show']);
        Route::patch('/{faceless}', [v1\Videos\FacelessController::class, 'update']);
        Route::post('/{faceless}/render', [v1\Videos\RenderFacelessController::class, 'store']);
        Route::post('/{faceless}/retry', [v1\Videos\RetryFacelessController::class, 'store']);
        Route::put('/{faceless}/scripts', [v1\Videos\ScriptGeneratorController::class, 'update']);
        Route::post('/{faceless}/convert', [v1\Videos\ConvertFacelessController::class, 'store']);
        Route::get('/{faceless}/assets', [v1\Videos\FacelessAssetsController::class, 'index']);
        Route::patch('/{faceless}/assets', [v1\Videos\FacelessAssetsController::class, 'update']);
        Route::get('/{faceless}/assets/{asset}', [v1\Videos\FacelessAssetsController::class, 'show']);

        Route::post('/{faceless}/media/upload', [v1\Videos\FacelessMediaManagerController::class, 'store']);
        Route::post('/{faceless}/media/transload', [v1\Videos\FacelessMediaManagerController::class, 'transload']);
        Route::post('/{faceless}/media/generate', [v1\Videos\FacelessMediaManagerController::class, 'update']);
        Route::get('/{faceless}/media/animation', [v1\Videos\FacelessMediaAnimationController::class, 'show']);
        Route::post('/{faceless}/media/animation', [v1\Videos\FacelessMediaAnimationController::class, 'store']);
        Route::post('/{faceless}/media/animation/bulk-status', [v1\Videos\FacelessMediaAnimationController::class, 'bulkStatus']);
        Route::post('/{faceless}/export', [v1\Videos\ExportFacelessController::class, 'store']);

        Route::post('/{faceless}/transcriptions/upload', [v1\Videos\AudioUploadController::class, 'store']);
        Route::post('/{faceless}/transcriptions', [v1\Videos\FacelessTranscriptionController::class, 'store']);

        Route::post('/{faceless}/scrape', [v1\Videos\FacelessScraperController::class, 'store']);
        Route::post('/{faceless}/scrape/images', [v1\Videos\FacelessImagesScraperController::class, 'store']);

        Route::prefix('options')->group(function () {
            Route::get('/fonts', [v1\Videos\FacelessOptionsController::class, 'fonts']);
            Route::get('/facts', [v1\Videos\FacelessOptionsController::class, 'facts']);
            Route::get('/genres', [v1\Videos\FacelessOptionsController::class, 'genres']);
            Route::get('/transitions', [v1\Videos\FacelessOptionsController::class, 'transitions']);
            Route::get('/backgrounds', [v1\Videos\FacelessOptionsController::class, 'backgrounds']);
            Route::get('/caption/effects', [v1\Videos\FacelessOptionsController::class, 'effects']);
            Route::get('/overlays', [v1\Videos\FacelessOptionsController::class, 'overlays']);
        });
    });

    Route::prefix('{video}/assets')->group(function () {
        Route::get('/', [v1\Videos\VideoMediaController::class, 'index']);
        Route::post('/', [v1\Videos\VideoMediaController::class, 'store']);
        Route::delete('/{asset}', [v1\Videos\VideoMediaController::class, 'destroy']);
        Route::post('/transload', [v1\Videos\TransloadMediaController::class, 'store']);
    });
});

Route::prefix('real-clones')->group(function () {
    Route::get('/avatars', [v1\RealClones\AvatarController::class, 'index']);
    Route::delete('/avatars/{avatar}', [v1\RealClones\AvatarController::class, 'destroy']);

    Route::post('/photo-avatars', [v1\RealClones\PhotoAvatarController::class, 'store']);

    Route::get('/{clone}', [v1\RealClones\RealCloneController::class, 'show']);
    Route::post('/', [v1\RealClones\RealCloneController::class, 'store']);
    Route::patch('/{clone}', [v1\RealClones\RealCloneController::class, 'update']);
    Route::delete('/{clone}', [v1\RealClones\RealCloneController::class, 'destroy']);
    Route::get('/{clone}/status', [v1\RealClones\RealCloneStatusController::class, 'show']);
    Route::put('/{clone}/scripts', [v1\RealClones\ScriptGeneratorController::class, 'update']);
    Route::post('/{clone}/scrape', [v1\RealClones\RealCloneScraperController::class, 'store']);
    Route::post('/{clone}/retries', [v1\RealClones\RealCloneRetrierController::class, 'store']);
    Route::post('/{clone}/generate', [v1\RealClones\GenerateRealCloneController::class, 'store']);
});

Route::prefix('speeches')->group(function () {
    Route::get('/voices', [v1\Speeches\VoiceController::class, 'index']);
});

Route::prefix('social-accounts')->group(function () {
    Route::get('/', [v1\Channels\SocialAccountController::class, 'index']);
});

Route::prefix('social')->group(function () {
    Route::get('/redirect/{provider}', [v1\Channels\SocialRedirectController::class, 'show']);
    Route::get('/callback/{provider}', [v1\Channels\SocialCallbackController::class, 'create']);
    Route::post('/disconnect/{provider}', [v1\Channels\SocialDisconnectController::class, 'update']);
    Route::post('/refresh/{provider}', [v1\Channels\SocialRefreshController::class, 'update']);

    Route::get('/callback/{provider}/channels', [v1\Channels\SocialChannelController::class, 'index']);
    Route::post('/callback/{provider}/channels', [v1\Channels\SocialChannelController::class, 'store']);
});

Route::prefix('publish')->group(function () {
    Route::post('/draft', [v1\Publication\DraftPublicationController::class, 'store']);
    Route::post('/youtube', [v1\Publication\YoutubePublicationController::class, 'store']);
    Route::post('/tiktok', [v1\Publication\TikTokPublicationController::class, 'store']);
    Route::post('/linkedin', [v1\Publication\LinkedInPublicationController::class, 'store']);
    Route::post('/facebook', [v1\Publication\FacebookPublicationController::class, 'store']);
    Route::post('/instagram', [v1\Publication\InstagramPublicationController::class, 'store']);
    Route::post('/threads', [v1\Publication\ThreadsPublicationController::class, 'store']);
});

Route::prefix('thumbnails')->group(function () {
    Route::get('/', [v1\Thumbnails\ThumbnailController::class, 'index']);
    Route::post('/', [v1\Thumbnails\ThumbnailController::class, 'store']);
    Route::delete('/{thumbnail}', [v1\Thumbnails\ThumbnailController::class, 'destroy']);
});

Route::prefix('metadata')->group(function () {
    Route::get('/youtube/categories', [v1\Metadata\YoutubeCategoryController::class, 'index']);
    Route::post('/tiktok/creator-info', [v1\Metadata\TikTokCreatorInfoController::class, 'index']);
    Route::get('/social-providers', [v1\Metadata\SocialProviderController::class, 'index']);
    Route::post('/generate/title', [v1\Metadata\GenerateTitleController::class, 'store']);
    Route::post('/generate/description', [v1\Metadata\GenerateDescriptionController::class, 'store']);
    Route::post('/generate/tags', [v1\Metadata\GenerateTagsController::class, 'store']);
    Route::post('/generate/context', [v1\Metadata\GenerateContextController::class, 'store']);
});

Route::prefix('stock-media')->group(function () {
    Route::get('/images/search', [v1\StockMedia\StockImageSearchController::class, 'index']);
    Route::get('/images/collections/{collection}', [v1\StockMedia\StockImageCollectionController::class, 'show']);

    Route::get('/videos/search', [v1\StockMedia\StockVideoSearchController::class, 'index']);
    Route::get('/videos/collections/{collection}', [v1\StockMedia\StockVideoCollectionController::class, 'show']);

    Route::get('/audios', [v1\StockMedia\StockAudioController::class, 'index']);
});

Route::prefix('clones')->group(function () {
    Route::get('/', [v1\Clones\CloneController::class, 'index']);
    Route::get('/{clonable}', [v1\Clones\CloneController::class, 'show']);
    Route::post('/voices', [v1\Clones\VoiceCloneController::class, 'store']);
    Route::post('/avatars', [v1\Clones\AvatarCloneController::class, 'store']);
    Route::delete('/{clonable}', [v1\Clones\CloneController::class, 'destroy']);
    Route::patch('/{clonable}/voices', [v1\Clones\VoiceCloneController::class, 'update']);
});

Route::prefix('user-feedback')->group(function () {
    Route::post('/', [v1\UserFeedback\UserFeedbackController::class, 'store']);
});

Route::prefix('media')->group(function () {
    Route::get('/metrics', [v1\Media\MediaMetricsController::class, 'index']);
    Route::post('/download', [v1\Assets\DownloadMediaController::class, 'bulk']);
    Route::get('/{uuid}/download', [v1\Assets\DownloadMediaController::class, 'download'])->name('download.media');
});

Route::prefix('subscriptions')->group(function () {
    Route::get('/plans', [v1\Subscriptions\SubscriptionPlanController::class, 'index']);
    Route::get('/plans/retention', [v1\Subscriptions\RetentionPlanController::class, 'index']);
    Route::post('/checkout', [v1\Subscriptions\CheckoutController::class, 'store']);
    Route::post('/customer-portal', [v1\Subscriptions\CustomerPortalController::class, 'show']);
    Route::get('/products/{product}', [v1\Subscriptions\ProductController::class, 'index']);
    Route::post('/purchases', [v1\Subscriptions\PurchaseController::class, 'store']);
    Route::post('google-play/checkout-completed', [v1\Subscriptions\GooglePlayCheckoutController::class, 'store']);

    Route::patch('/swap-plan', [v1\Subscriptions\SwapPlanController::class, 'update']);

    Route::post('/payment-intent', [v1\Subscriptions\PaymentIntentController::class, 'store']);
    Route::post('/subscribe', [v1\Subscriptions\SubscriptionController::class, 'store']);

    Route::get('/details', [v1\Subscriptions\SubscriptionController::class, 'show']);
    Route::delete('/cancel', [v1\Subscriptions\SubscriptionCancelController::class, 'destroy']);
    Route::put('/cancel-downgrade', [v1\Subscriptions\CancelDowngradeController::class, 'update']);
    Route::post('/resume', [v1\Subscriptions\SubscriptionResumeController::class, 'store']);
    Route::post('/end-trial', [v1\Subscriptions\SubscriptionTrialController::class, 'store']);
    Route::post('/extend-trial', [v1\Subscriptions\SubscriptionTrialController::class, 'update']);
    Route::post('/coupons/redeem', [v1\Subscriptions\CouponRedeemController::class, 'store']);

    Route::post('/proration', [v1\Subscriptions\ProrationController::class, 'show']);

    Route::prefix('storage')->group(function () {
        Route::put('/', [v1\Subscriptions\StorageController::class, 'update']);
        Route::delete('/', [v1\Subscriptions\StorageController::class, 'destroy']);
        Route::get('/plans', [v1\Subscriptions\StoragePlanController::class, 'index']);
    });
});

Route::prefix('previews')->group(function () {
    Route::get('/audios', [v1\Previews\AudioController::class, 'index']);
    Route::post('/render', [v1\Previews\RenderController::class, 'store']);
    Route::get('/{video}', [v1\Previews\RenderController::class, 'show']);
});

Route::post('/track-events', [v1\SocialMedia\TrackEventController::class, 'store']);

Route::fallback(fn () => response()->json('Not Found!', 404));
