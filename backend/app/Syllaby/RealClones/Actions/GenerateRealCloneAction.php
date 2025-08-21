<?php

namespace App\Syllaby\RealClones\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Speeches\Speech;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Speeches\Vendors\Speaker;
use App\Syllaby\Speeches\Enums\SpeechStatus;
use App\Syllaby\RealClones\Vendors\Presenter;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\Speeches\Jobs\TriggerSpeechGeneration;
use App\Syllaby\RealClones\Jobs\TriggerRealCloneGeneration;

class GenerateRealCloneAction
{
    /**
     * Initiates the generation process.
     */
    public function handle(RealClone $clone, User $user): RealClone
    {
        if (! $this->shouldGenerateClone($clone)) {
            return $clone;
        }

        $clone->fill(['status' => RealCloneStatus::GENERATING]);

        $speech = $this->resolveSpeech($clone);

        $this->charge($user, $clone, $speech);

        Bus::chain([
            new TriggerSpeechGeneration($clone, $speech),
            new TriggerRealCloneGeneration($clone, $speech),
        ])->dispatch();

        return tap($clone)->save();
    }

    /**
     * Fetch the real clone speech.
     */
    private function resolveSpeech(RealClone $clone): Speech
    {
        $lookup = [
            'user_id' => $clone->user_id,
            'real_clone_id' => $clone->id,
        ];

        $attributes = [
            'synced_at' => null,
            'voice_id' => $clone->voice_id,
            'provider' => $clone->voice->provider,
            'status' => SpeechStatus::PROCESSING,
        ];

        return $clone->speech()->updateOrCreate($lookup, $attributes);
    }

    /**
     * Charge user credits for speech and real clone generation.
     */
    private function charge(User $user, RealClone $clone, Speech $speech): void
    {
        if ($this->shouldChargeSpeech($clone)) {
            $speech->setRelation('user', $user);
            Speaker::driver($speech->provider)->charge($speech, $clone->script);
        }

        $clone->setRelation('user', $user);
        Presenter::driver($clone->provider)->charge($clone);
    }

    /**
     * Whether a real generation process should start.
     */
    private function shouldGenerateClone(RealClone $clone): bool
    {
        return Arr::get($clone->hash, 'real-clone') !== $clone->hashes('real-clone');
    }

    /**
     * Whether a user should be charged credits for the speech.
     */
    private function shouldChargeSpeech(RealClone $clone): bool
    {
        return Arr::get($clone->hash, 'speech') !== $clone->hashes('speech');
    }
}
