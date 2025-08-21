<?php

namespace App\Syllaby\Animation\Vendors;

use Exception;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Animation\Enums\MinimaxStatus;
use App\Syllaby\Animation\DTOs\AnimationStatusData;
use App\Syllaby\Animation\Contracts\AnimationGenerator;
use App\Syllaby\Animation\DTOs\AnimationGenerationResponse;

class Minimax implements AnimationGenerator
{
    public function generate(Faceless $faceless, string $mediaUrl, ?string $prompt = null): AnimationGenerationResponse
    {
        $params = [
            'model' => $model = 'MiniMax-Hailuo-02',
            'first_frame_image' => $mediaUrl,
            'prompt' => $prompt,
            'callback_url' => route('minimax.webhook', ['faceless_id' => $faceless->id]),
        ];

        if (config('services.animation.use_polling')) {
            Arr::forget($params, 'callback_url');
        }

        $response = $this->http()->asJson()->post('video_generation', $params);

        $status = MinimaxStatus::from((int) $response->json('base_resp.status_code'));

        if ($status->isFailed()) {
            Log::error('Minimax - Failed to initiate animation generation', ['response' => $response->json()]);
            throw new Exception($status->getPublicMessage());
        }

        return AnimationGenerationResponse::fromMinimax($response->json(), [
            'model' => $model,
            'description' => $prompt,
        ]);
    }

    public function status(int $identifier): AnimationStatusData
    {
        $response = $this->http()->asJson()->get('query/video_generation', [
            'task_id' => $identifier,
        ]);

        $status = MinimaxStatus::from((int) $response->json('base_resp.status_code'));

        if ($status->isFailed()) {
            Log::alert('Minimax - Failed to get animation status', ['response' => $response->json()]);
        }

        return AnimationStatusData::fromResponse($response->json());
    }

    public function getDownloadUrl(int $identifier): string
    {
        $response = $this->http()->get('files/retrieve', [
            'file_id' => $identifier,
        ]);

        $status = MinimaxStatus::from((int) $response->json('base_resp.status_code'));

        if ($status->isFailed()) {
            Log::alert('Minimax - Failed to get download URL', ['response' => $response->json()]);
            throw new Exception($status->getPublicMessage());
        }

        return $response->json('file.download_url') ?? throw new Exception('Unable to get download URL.');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('services.minimax.base_url'))
            ->withToken(config('services.minimax.api_key'))
            ->timeout(120);
    }
}
