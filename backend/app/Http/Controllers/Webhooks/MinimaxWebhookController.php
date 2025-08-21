<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Animation\Enums\MinimaxStatus;
use App\Syllaby\Animation\Jobs\DownloadAnimation;
use App\Syllaby\Animation\Events\AnimationGenerationFailed;

class MinimaxWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->get('challenge')]);
        }

        $status = $this->resolveStatus($request->get('status'));

        if ($status === AssetStatus::PROCESSING) {
            return $this->success();
        }

        Log::alert('Minimax Webhook received', ['request' => $request->all()]);

        if (! $asset = $this->fetchAsset($request->get('task_id'))) {
            Log::error('Minimax Webhook - Asset not found [{id}]', ['id' => $request->get('task_id')]);

            return $this->success();
        }

        $faceless = Faceless::find($request->get('faceless_id'));

        if ($status === AssetStatus::FAILED) {
            $this->fail($asset, $request->all(), $faceless);

            return $this->success();
        }

        $response = MinimaxStatus::from((int) $request->get('base_resp.status_code'));

        if ($response->isFailed()) {
            $this->fail($asset, $request->all(), $faceless);

            return $this->success();
        }

        dispatch(new DownloadAnimation($asset, $request->get('file_id'), $faceless));

        return $this->success();
    }

    private function resolveStatus(string $status): AssetStatus
    {
        return match (Str::lower($status)) {
            'success' => AssetStatus::SUCCESS,
            'processing', 'queueing', 'preparing' => AssetStatus::PROCESSING,
            default => AssetStatus::FAILED,
        };
    }

    private function fetchAsset(string $identifier): ?Asset
    {
        return Asset::where('provider_id', $identifier)->whereNotNull('provider_id')->first();
    }

    private function fail(Asset $asset, array $response, ?Faceless $faceless = null): void
    {
        Log::error('Minimax Webhook - Failed to generate animation', ['asset' => $asset->id, 'response' => $response]);

        event(new AnimationGenerationFailed($asset, $faceless));
    }

    private function success(string $message = 'Webhook Handled'): JsonResponse
    {
        return response()->json(['message' => $message], 200);
    }
}
