<?php

namespace App\Syllaby\Videos\Listeners;

use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;

class HandleFailedFacelessGeneration
{
    /**
     * Handle the event.
     */
    public function handle(FacelessGenerationFailed $event): void
    {
        DB::transaction(fn () => $this->abort($event->faceless));
    }

    /**
     * Aborts the faceless video generation and refunds the user.
     */
    private function abort(Faceless $faceless): void
    {
        $faceless->load(['user', 'video']);

        $user = $faceless->user;
        $video = $faceless->video;

        if (! $user->subscribed()) {
            return;
        }

        $video->update([
            'url' => null,
            'synced_at' => null,
            'status' => VideoStatus::FAILED,
        ]);

        $event = ($video->exports === 0)
            ? CreditEventEnum::FACELESS_VIDEO_GENERATED
            : CreditEventEnum::FACELESS_VIDEO_EXPORTED;

        (new CreditService($user))->refund($faceless, $event);
    }
}
