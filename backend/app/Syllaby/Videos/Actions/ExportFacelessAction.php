<?php

namespace App\Syllaby\Videos\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Actions\ChargeFacelessVideoAction;
use App\Syllaby\Credits\Exceptions\InsufficientCreditsException;

class ExportFacelessAction
{
    public function __construct(protected UpdateFacelessAction $action) {}

    /**
     * Handles the faceless video editing operation.
     *
     * @throws InsufficientCreditsException
     */
    public function handle(Faceless $faceless, User $user, array $input): Faceless
    {
        $hash = $faceless->hash;

        $faceless = DB::transaction(function () use ($faceless, $input) {
            if ($transcriptions = Arr::get($input, 'transcriptions')) {
                $this->captions($faceless, $transcriptions);
            }

            return $this->action->handle($faceless, $input);
        });

        if ($this->shouldCharge($faceless, $hash)) {
            $this->charge($user, $faceless);
        }

        tap($faceless->video, fn ($video) => $video->update([
            'exports' => $video->exports + 1,
            'status' => VideoStatus::RENDERING,
        ]));

        return $faceless;
    }

    /**
     * Determines if the user should be charged for the faceless video.
     */
    private function shouldCharge(Faceless $faceless, array $hash): bool
    {
        if ($faceless->wasChanged(['music_id', 'watermark_id'])) {
            return true;
        }

        return Arr::get($hash, 'options') !== Arr::get($faceless->hash, 'options');
    }

    /**
     * Charges the user for the faceless video.
     *
     * @throws InsufficientCreditsException
     */
    private function charge(User $user, Faceless $faceless): void
    {
        $event = CreditEventEnum::FACELESS_VIDEO_EXPORTED;

        app(ChargeFacelessVideoAction::class)->handle($faceless, $user, $event);
    }

    /**
     * Updates the captions with the provided user changes.
     */
    private function captions(Faceless $faceless, array $transcriptions): void
    {
        $captions = $faceless->captions()->first();

        if (! $captions || blank($captions->content)) {
            return;
        }

        $transcriptions = collect($captions->content)->map(function (array $caption, int $index) use ($transcriptions) {
            if (! Arr::has($transcriptions, $index)) {
                return $caption;
            }

            $words = Arr::get($transcriptions, $index, []);

            $caption['text'] = implode(' ', $words);
            $caption['words'] = Arr::map($caption['words'], fn (array $word, int $idx) => [
                ...$word, 'text' => $words[$idx],
            ]);

            return $caption;
        });

        $captions->update(['content' => $transcriptions->toArray()]);
    }
}
