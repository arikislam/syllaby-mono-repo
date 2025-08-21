<?php

namespace App\Syllaby\RealClones\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\RealClones\Enums\RealCloneStatus;

class UpdateRealCloneAction
{
    /**
     * Update in storage the given real clone record.
     */
    public function handle(RealClone $clone, array $input): RealClone
    {
        return tap($clone)->update([
            'status' => $this->resolveStatus($clone, $input),
            'script' => Arr::get($input, 'script', $clone->script),
            'provider' => Arr::get($input, 'provider', $clone->provider),
            'voice_id' => Arr::get($input, 'voice_id', $clone->voice_id),
            'avatar_id' => Arr::get($input, 'avatar_id', $clone->avatar_id),
        ]);
    }

    /**
     * Set status to draft if there's changes.
     */
    private function resolveStatus(RealClone $clone, array $input): RealCloneStatus
    {
        $script = Arr::get($input, 'script', $clone->script);
        $voiceId = Arr::get($input, 'voice_id', $clone->voice_id);
        $avatarId = Arr::get($input, 'avatar_id', $clone->avatar_id);

        $rehashed = md5(serialize([$voiceId, $avatarId, $script]));

        return $clone->hash === $rehashed ? $clone->status : RealCloneStatus::DRAFT;
    }
}
