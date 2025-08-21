<?php

namespace App\Syllaby\Characters\Jobs;

use Arr;
use Log;
use Throwable;
use App\Syllaby\Characters\Character;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Characters\Events\CharacterGenerationFailed;
use App\Syllaby\Characters\Notifications\CharacterGenerationSucceededNotification;

class HandleCharacterFinalTrainingWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Character $character, protected array $response) {}

    public function handle(): void
    {
        if (Arr::get($this->response, 'status') != 'succeeded') {
            $this->fail('Training failed with status: '.Arr::get($this->response, 'status'));
        }

        $this->character->update([
            'status' => CharacterStatus::READY,
            'trigger' => Arr::get($this->response, 'input.trigger_word'),
            'model' => Arr::get($this->response, 'output.version'),
            'provider_id' => null,
        ]);

        $this->character->user->notify(new CharacterGenerationSucceededNotification($this->character));
    }

    public function failed(Throwable $throwable): void
    {
        $this->character->update(['status' => CharacterStatus::MODEL_TRAINING_FAILED]);

        event(new CharacterGenerationFailed($this->character));

        Log::error('Custom Character Final Training job failed', [
            'character_id' => $this->character->id,
            'error' => $throwable->getMessage(),
        ]);
    }
}
