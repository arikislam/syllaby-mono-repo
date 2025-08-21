<?php

namespace App\Syllaby\Animation\Listeners;

use DB;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Videos\Events\VideoModified;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Animation\Notifications\AnimationFailed;
use App\Syllaby\Animation\Events\AnimationGenerationFailed;

class HandleFailedAnimationGeneration
{
    public function handle(AnimationGenerationFailed $event): void
    {
        DB::transaction(fn () => $this->abort($event->faceless, $event->asset));

        $event->faceless->user->notify(new AnimationFailed($event->faceless));
    }

    public function abort(Faceless $faceless, Asset $asset): void
    {
        $asset->update(['status' => AssetStatus::FAILED]);

        (new CreditService)->setUser($faceless->user)->refund($faceless, CreditEventEnum::IMAGE_ANIMATED);

        event(new VideoModified($faceless->video));
    }
}
