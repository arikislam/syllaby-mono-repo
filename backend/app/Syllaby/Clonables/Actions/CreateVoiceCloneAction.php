<?php

namespace App\Syllaby\Clonables\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Syllaby\Clonables\Jobs\ProcessClonedVoiceJob;

class CreateVoiceCloneAction
{
    /**
     * Create a voice clone intent.
     */
    public function handle(User $user, array $input): Clonable
    {
        $voice = $this->createVoice($user, $input);
        $clonable = $this->intent($voice, $input);

        $this->upload($clonable, Arr::get($input, 'samples'));
        dispatch(new ProcessClonedVoiceJob($user, $clonable, $voice));

        return $clonable;
    }

    /**
     * Creates user's voice record in storage.
     */
    private function createVoice(User $user, array $input): Voice
    {
        return Voice::create([
            'is_active' => false,
            'user_id' => $user->id,
            'words_per_minute' => 140,
            'type' => Voice::REAL_CLONE,
            'name' => Arr::get($input, 'name'),
            'gender' => Arr::get($input, 'gender'),
            'provider' => Arr::get($input, 'provider'),
            'accent' => Arr::get($input, 'accent', 'english non-native'),
        ]);
    }

    /**
     * Creates a clonable intent
     */
    private function intent(Voice $voice, array $input): Clonable
    {
        return Clonable::create([
            'model_id' => $voice->id,
            'user_id' => $voice->user_id,
            'model_type' => (new Voice)->getMorphClass(),
            'status' => CloneStatus::PENDING,
            'metadata' => Arr::only($input, ['description']),
        ]);
    }

    /**
     * Upload voice samples.
     */
    private function upload(Clonable $clonable, array $samples): void
    {
        foreach ($samples as $sample) {
            $clonable->addMedia($sample)->addCustomHeaders(['ACL' => 'public-read'])->toMediaCollection();
        }
    }
}
