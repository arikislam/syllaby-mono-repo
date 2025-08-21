<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;
use App\Syllaby\Generators\Jobs\ProcessIncomingPrediction;

class ReplicateWebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('verify.replicate');
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request): JsonResponse
    {
        $context = $request->input('context');
        $input = ImageGeneratorResponse::fromReplicate(
            $request->except(['logs', 'metrics', 'context'])
        );

        dispatch(new ProcessIncomingPrediction($input, $context));

        return response()->json(['message' => 'Webhook Handled']);
    }
}
