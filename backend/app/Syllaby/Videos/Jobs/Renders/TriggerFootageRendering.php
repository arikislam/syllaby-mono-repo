<?php

namespace App\Syllaby\Videos\Jobs\Renders;

use Throwable;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Videos\Vendors\Renders\Studio;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Videos\Exceptions\RenderEngineFailedException;

class TriggerFootageRendering implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Video $video, protected Footage $footage)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->video->setRelation('footage', $this->footage);
            $response = Studio::driver($this->video->provider)->render($this->video);

            $this->video->update([
                'url' => Arr::get($response, 'url'),
                'status' => Arr::get($response, 'status'),
                'provider_id' => Arr::get($response, 'provider_id'),
            ]);
        } catch (RenderEngineFailedException $exception) {
            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Unable start rendering video {id} with {provider}', [
            'id' => $this->video->id,
            'provider' => $this->video->provider,
            'error' => $exception->getMessage(),
        ]);

        DB::transaction(function () {
            $this->refund();
            $this->video->update(['status' => VideoStatus::FAILED, 'url' => null, 'synced_at' => null]);
        });
    }

    /**
     * Refund the user's credits for the failed video render.
     */
    private function refund(): void
    {
        (new CreditService($this->video->user))->refund($this->video);
    }
}
