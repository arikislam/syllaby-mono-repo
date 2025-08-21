<?php

namespace App\Syllaby\Credits\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Credits\CreditHistory;
use App\System\Traits\DetectsLanguage;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Credits\Exceptions\InsufficientCreditsException;

class ChargeFacelessVideoAction
{
    use DetectsLanguage;

    /**
     * Charge the user based on video estimated duration.
     *
     * @throws Exception
     */
    public function handle(Faceless $faceless, ?User $user = null, ?CreditEventEnum $event = null): void
    {
        $user ??= $faceless->user;

        $event ??= CreditEventEnum::FACELESS_VIDEO_GENERATED;
        $unit = Arr::get(config('credit-engine.events'), "{$event->value}.min_amount");

        $amount = $this->duration($faceless) * $unit;

        if ($user->credits() < $amount) {
            throw new InsufficientCreditsException('You do not have enough credits to render this video');
        }

        (new CreditService($user))->decrement(
            type: $event,
            creditable: $faceless,
            amount: $amount,
            label: $this->resolveLabel($faceless)
        );
    }

    /**
     * Calculates an estimation of the video duration in minutes.
     *
     * @throws Exception
     */
    private function duration(Faceless $faceless): int
    {
        $duration = match (true) {
            $faceless->is_transcribed => $this->fromAudio($faceless),
            default => $this->fromText($faceless),
        };

        return max(1, (int) ceil($duration / 60));
    }

    /**
     * Calculates the duration from the uploaded audio.
     *
     * @throws Exception
     */
    private function fromAudio(Faceless $faceless): float
    {
        $voiceover = $faceless->getMedia('script');

        if ($voiceover->isEmpty()) {
            throw new Exception('Transcription voiceover not found.');
        }

        return $voiceover->sum('custom_properties.duration');
    }

    /**
     * Calculates the duration from the provided script.
     */
    private function fromText(Faceless $faceless): float
    {
        if ($faceless->length === 'short') {
            return 1;
        }

        $faceless->load(['voice', 'generator']);

        $wpm = max(60, $faceless->voice->words_per_minute);
        $language = $this->locale($faceless->generator?->language);

        return reading_time($faceless->script, $wpm, $language);
    }

    /**
     * Resolve the label for the credit history.
     */
    private function resolveLabel(Faceless $faceless): string
    {
        $video = $faceless->video;
        $excerpt = Str::limit($faceless->script, CreditHistory::TRUNCATED_LENGTH);

        if ($video->title === 'Untitled' || blank($video->title)) {
            return "{$video->title} - {$excerpt}";
        }

        return $video->title;
    }
}
