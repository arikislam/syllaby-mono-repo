<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Surveys\Answer;
use App\Syllaby\Trackers\Tracker;
use App\Syllaby\Bookmarks\Bookmark;
use App\Syllaby\Scraper\ScraperLog;
use App\Syllaby\Presets\FacelessPreset;
use App\Syllaby\Subscriptions\Purchase;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Subscriptions\CardFingerprint;

class DeleteUserInteractionData implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function handle(): void
    {
        $this->user->clearMediaCollection('welcome-video');

        Answer::where('user_id', $this->user->id)->delete();

        Bookmark::where('user_id', $this->user->id)->delete();

        FacelessPreset::where('user_id', $this->user->id)->delete();

        Feature::for($this->user)->forget(Feature::defined());

        ScraperLog::where('user_id', $this->user->id)->delete();

        CardFingerprint::where('user_id', $this->user->id)->delete();

        Tracker::where('user_id', $this->user->id)->delete();

        Purchase::where('user_id', $this->user->id)->delete();

        $this->user->industries()->detach();
    }
}
