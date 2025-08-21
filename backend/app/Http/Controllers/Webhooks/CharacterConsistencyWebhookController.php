<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;
use App\Syllaby\Generators\Jobs\ProcessIncomingPrediction;

class CharacterConsistencyWebhookController extends Controller
{
    /**
     * Handle the webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        if (in_array($request->input('status'), ['processing', 'queued'])) {
            return response()->json(['message' => 'Webhook Handled'], 200);
        }

        $context = $request->input('context');
        $input = ImageGeneratorResponse::fromSyllaby([
            ...$request->except(['context']), 'id' => $request->input('job_id'),
        ]);

        dispatch(new ProcessIncomingPrediction($input, $context));

        return response()->json(['message' => 'Webhook Handled'], 200);
    }
}
