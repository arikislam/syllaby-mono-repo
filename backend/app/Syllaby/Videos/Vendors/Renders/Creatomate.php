<?php

namespace App\Syllaby\Videos\Vendors\Renders;

use Creatomate\Source;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Video;
use App\Syllaby\Credits\CreditHistory;
use App\System\Traits\HandlesWatermark;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Videos\Contracts\RenderContract;
use Illuminate\Http\Client\PendingRequest as Http;
use App\Syllaby\Videos\Exceptions\RenderEngineFailedException;
use App\Syllaby\Videos\Exceptions\RenderEngineStatusException;

class Creatomate implements RenderContract
{
    use HandlesWatermark;

    public function __construct(protected Http $http)
    {
        //
    }

    /**
     * {@inheritDoc}
     *
     * @throws RenderEngineFailedException
     */
    public function render(Video $video): array
    {
        $video->load(['user', 'footage.timeline']);

        $timeline = $video->footage->timeline;
        $source = $timeline->content;

        if ($video->user->onTrial()) {
            $source = $this->addSyllabyWatermark($source);
        }

        $response = $this->http->post('/renders', [
            'output_format' => 'mp4',
            'source' => (new Source($source))->toArray(),
            'webhook_url' => route('creatomate.webhook'),
        ]);

        if ($response->failed()) {
            throw new RenderEngineFailedException('Creatomate failed to start rendering');
        }

        $timeline->update(['content' => $source]);

        $render = $response->json('0');

        return [
            'url' => Arr::get($render, 'url'),
            'provider_id' => Arr::get($render, 'id'),
            'status' => $this->status(Arr::get($render, 'status')),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function charge(Video $video, int $duration): void
    {
        (new CreditService($video->user))->decrement(
            type: CreditEventEnum::VIDEO_GENERATED,
            creditable: $video,
            amount: video_render_credits($video->provider, $duration),
            label: Str::limit($video->title, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws RenderEngineStatusException
     */
    public function ping(Video $video): Video
    {
        $response = $this->http->get("/render/{$video->provider_id}");

        if ($response->failed()) {
            throw new RenderEngineStatusException('Creatomate failed to fetch render status');
        }

        return tap($video)->update([
            'status' => $this->status(Arr::get($response, 'status')),
        ]);
    }

    /**
     * Maps the Creatomate render status.
     */
    private function status(string $status): VideoStatus
    {
        return match ($status) {
            'failed' => VideoStatus::FAILED,
            'succeeded' => VideoStatus::COMPLETED,
            default => VideoStatus::RENDERING,
        };
    }
}
