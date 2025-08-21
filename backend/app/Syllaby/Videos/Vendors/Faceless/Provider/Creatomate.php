<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Provider;

use Creatomate\Source;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Videos\Enums\VideoProvider;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\Videos\Exceptions\RenderEngineFailedException;

class Creatomate
{
    /**
     * @throws RenderEngineFailedException|ConnectionException
     */
    public function render(array $timeline, array $options = []): array
    {
        $response = $this->http()->throw()->post('/renders', [
            'output_format' => 'mp4',
            'source' => (new Source($timeline))->toArray(),
            'webhook_url' => config('services.creatomate.webhook_url'),
            'metadata' => json_encode(Arr::only($options, 'faceless_id')),
        ]);

        if ($response->failed()) {
            throw new RenderEngineFailedException('Creatomate failed to start rendering');
        }

        return [
            'url' => $response->json('0.url'),
            'provider_id' => $response->json('0.id'),
            'provider' => VideoProvider::CREATOMATE->value,
            'status' => $this->status($response->json('0.status')),
        ];
    }

    public function credits(string $provider, int $duration): int
    {
        if ($duration <= 60) {
            return config('credit-engine.video.creatomate');
        }

        return video_render_credits($provider, $duration);
    }

    public function charge(int $credits, Faceless $faceless, User $user): void
    {
        $video = $faceless->video;

        (new CreditService($user))->decrement(
            type: CreditEventEnum::VIDEO_GENERATED,
            creditable: $video,
            amount: $credits,
            label: Str::limit($video->title, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    private function status(string $status): VideoStatus
    {
        return match ($status) {
            'failed' => VideoStatus::FAILED,
            'succeeded' => VideoStatus::COMPLETED,
            default => VideoStatus::RENDERING,
        };
    }

    private function http(): PendingRequest
    {
        return Http::asJson()
            ->baseUrl(config('services.creatomate.url'))
            ->withToken(config('services.creatomate.key'));
    }
}
