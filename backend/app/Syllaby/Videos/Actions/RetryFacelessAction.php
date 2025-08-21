<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Actions\ChargeFacelessVideoAction;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;

class RetryFacelessAction
{
    public function __construct(protected RenderFacelessAction $render, protected ChargeFacelessVideoAction $charge) {}

    public function handle(Faceless $faceless, User $user): Faceless
    {
        if ($faceless->isExported()) {
            $this->rebuildSource($faceless, $user);
        } else {
            $faceless = $this->render->handle($faceless, $user, []);
        }

        return tap($faceless, function (Faceless $faceless) {
            return $faceless->video()->increment('retries');
        });
    }

    private function rebuildSource(Faceless $faceless, User $user): void
    {
        if ($this->shouldCharge($faceless, $user)) {
            $this->charge->handle($faceless, $user, CreditEventEnum::FACELESS_VIDEO_EXPORTED);
        }

        $faceless->video()->update(['status' => VideoStatus::RENDERING]);

        dispatch(new BuildFacelessVideoSource($faceless));
    }

    private function shouldCharge(Faceless $faceless, User $user): bool
    {
        return CreditHistory::where('creditable_id', $faceless->id)
            ->where('creditable_type', $faceless->getMorphClass())
            ->where('user_id', $user->id)
            ->where('description', CreditEventEnum::REFUNDED_CREDITS->value)
            ->exists();
    }
}
