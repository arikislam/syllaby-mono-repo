<?php

namespace App\Providers;

use App\Syllaby;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Syllaby\Assets\Asset::class => Syllaby\Assets\Policies\AssetPolicy::class,
        Syllaby\Assets\Media::class => Syllaby\Assets\Policies\MediaPolicy::class,
        Syllaby\Videos\Video::class => Syllaby\Videos\Policies\VideoPolicy::class,
        Syllaby\Ideas\Keyword::class => Syllaby\Ideas\Policies\KeywordPolicy::class,
        Syllaby\Planner\Event::class => Syllaby\Planner\Policies\EventPolicy::class,
        Syllaby\Videos\Footage::class => Syllaby\Videos\Policies\FootagePolicy::class,
        Syllaby\Videos\Faceless::class => Syllaby\Videos\Policies\FacelessPolicy::class,
        Syllaby\RealClones\Avatar::class => Syllaby\RealClones\Policies\AvatarPolicy::class,
        Syllaby\Clonables\Clonable::class => Syllaby\Clonables\Policies\ClonablePolicy::class,
        Syllaby\Generators\Generator::class => Syllaby\Generators\Policies\GeneratorPolicy::class,
        Syllaby\RealClones\RealClone::class => Syllaby\RealClones\Policies\RealClonePolicy::class,
        Syllaby\Schedulers\Scheduler::class => Syllaby\Schedulers\Policies\SchedulerPolicy::class,
        Syllaby\Schedulers\Occurrence::class => Syllaby\Schedulers\Policies\OccurrencePolicy::class,
        Syllaby\Characters\Character::class => Syllaby\Characters\Policies\CharacterPolicy::class,
        Syllaby\Presets\FacelessPreset::class => Syllaby\Presets\Policies\FacelessPresetPolicy::class,
        Syllaby\Publisher\Publications\Publication::class => Syllaby\Publisher\Publications\Policy\PublicationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return in_array($user->email, ['helder@syllaby.io', 'sharryy@syllaby.io']);
        });

        $this->defineFeatures();
    }

    /**
     * Define for each feature its resolver.
     */
    private function defineFeatures(): void
    {
        Feature::define('video', fn (User $user) => match (true) {
            $user->subscribed() => $user->plan->details('features.video'),
            default => false
        });

        Feature::define('calendar', fn (User $user) => match (true) {
            $user->subscribed() => $user->plan->details('features.calendar'),
            default => false,
        });

        Feature::define('article_writer', fn (User $user) => match (true) {
            $user->subscribed() => $user->plan->details('features.article_writer'),
            default => false,
        });

        Feature::define('consistency_tracker', fn (User $user) => match (true) {
            $user->subscribed() => $user->plan->details('features.consistency_tracker'),
            default => false,
        });

        Feature::define('thumbnails', fn (User $user) => match (true) {
            $user->subscribed() => $user->plan->details('features.thumbnails'),
            default => false,
        });

        collect(['tiktok', 'facebook', 'youtube', 'linkedin', 'instagram', 'threads'])->each(function ($platform) {
            Feature::define("publish_{$platform}", fn (User $user) => match (true) {
                $user->subscribed() => $user->plan->details("features.{$platform}"),
                default => false,
            });
        });

        Feature::define('max_voice_clones', fn (User $user) => match (true) {
            ! $user->subscribed(), $user->onTrial() => '0',
            default => $user->plan->details('features.max_voice_clones')
        });

        Feature::define('max_scheduled_posts', fn (User $user) => match (true) {
            ! $user->subscribed(), $user->onTrial() => '0',
            default => $user->plan->details('features.max_scheduled_posts')
        });

        Feature::define('max_scheduled_weeks', fn (User $user) => match (true) {
            ! $user->subscribed(), $user->onTrial() => '0',
            default => $user->plan->details('features.max_scheduled_weeks'),
        });

        Feature::define('max_storage', function (User $user) {
            return match (true) {
                ! $user->subscribed() => '0',
                $user->onTrial() => '5368709120',
                default => (string) $this->calculateStorageLimit($user)
            };
        });

        Feature::define('render_driver', fn () => 'remotion');

        Feature::define('watermark', fn () => false);
    }

    /**
     * Calculate the storage limit for the user.
     */
    private function calculateStorageLimit(User $user): int
    {
        $subscription = $user->subscription('default');
        $base = $user->plan->details('features.storage') ?? 0;

        // For Google Play subscriptions, storage add-ons are handled differently
        if ($user->usesGooglePlay()) {
            // Google Play doesn't support storage add-ons like Stripe does
            // Return the base storage limit from the plan
            return $base;
        }

        $recurrence = $user->plan->type === 'month' ? 'monthly' : 'yearly';
        $price = config("services.stripe.add_ons.storage.{$recurrence}");

        if (! $price = $subscription->items()->where('stripe_price', $price)->first()) {
            return $base;
        }

        return $base + ($price->quantity * 1024 * 1024 * 1024);
    }
}
