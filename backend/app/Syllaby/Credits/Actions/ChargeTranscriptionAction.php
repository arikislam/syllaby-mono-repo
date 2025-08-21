<?php

namespace App\Syllaby\Credits\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Syllaby\Generators\Vendors\Transcribers\Transcriber;

class ChargeTranscriptionAction
{
    /**
     * Charge credits for audio transcription based on duration.
     */
    public function handle(User $user, Media $audio, string $provider = 'whisper'): void
    {
        $duration = $audio->getCustomProperty('duration', 0);

        $credits = Transcriber::driver($provider)->credits($duration);

        (new CreditService($user))->decrement(
            type: CreditEventEnum::AUDIO_TRANSCRIPTION,
            creditable: $audio,
            amount: $credits,
            label: 'Audio transcription',
        );
    }
}
