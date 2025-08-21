<?php

namespace App\Syllaby\Animation\Jobs;

use Log;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Videos\Events\VideoModified;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Animation\Contracts\AnimationGenerator;
use App\Syllaby\Animation\Events\AnimationGenerationFailed;
use App\Syllaby\Animation\Notifications\AnimationSuccessful;

class DownloadAnimation implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public function __construct(public Asset $asset, public int $identifier, public ?Faceless $faceless = null) {}

    public function handle(AnimationGenerator $animation, TransloadMediaAction $action): void
    {
        $downloadUrl = $animation->getDownloadUrl($this->identifier);

        $action->handle($this->asset, $downloadUrl);
        $this->asset->update(['status' => AssetStatus::SUCCESS]);

        if (blank($this->faceless)) {
            return;
        }

        $this->faceless->user->notify(new AnimationSuccessful($this->faceless));

        event(new VideoModified($this->faceless->video));
    }

    public function backoff(): int
    {
        return 2 ** $this->attempts();
    }

    public function failed(): void
    {
        Log::alert("Failed to download animation for Faceless [{$this->faceless->id}] - Asset [{$this->asset->id}]");

        event(new AnimationGenerationFailed($this->asset, $this->faceless));
    }
}
