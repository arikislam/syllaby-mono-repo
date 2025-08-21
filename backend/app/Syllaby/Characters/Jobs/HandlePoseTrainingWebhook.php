<?php

namespace App\Syllaby\Characters\Jobs;

use Arr;
use Log;
use Exception;
use App\Syllaby\Characters\Character;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Characters\Events\CharacterGenerationFailed;

class HandlePoseTrainingWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Character $character, protected array $response) {}

    public function handle(): void
    {
        if (Arr::get($this->response, 'status') != 'succeeded') {
            $this->fail('Poses failed with status: '.Arr::get($this->response, 'status'));
        }

        $poses = Arr::get($this->response, 'output');

        if (empty($poses) || count(Arr::wrap($poses)) < 10) {
            throw new Exception("Insufficient poses generated for character - {$this->character->id}. Expected at least 10, got ".count($poses));
        }

        foreach ($poses as $index => $pose) {
            app(TransloadMediaAction::class)->handle($this->character, $pose, 'poses', order: $index);
        }

        $this->character->update(['status' => CharacterStatus::POSE_READY, 'provider_id' => null]);

        dispatch(new TriggerLoraTraining($this->character));
    }

    public function failed(Exception $exception): void
    {
        $this->character->update(['status' => CharacterStatus::POSE_FAILED]);

        event(new CharacterGenerationFailed($this->character));

        Log::error('Custom Character Pose Training job failed', [
            'character_id' => $this->character->id,
            'error' => $exception->getTraceAsString(),
        ]);
    }
}
