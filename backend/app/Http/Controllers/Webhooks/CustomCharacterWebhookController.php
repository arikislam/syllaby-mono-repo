<?php

namespace App\Http\Controllers\Webhooks;

use Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Characters\Character;
use App\Syllaby\Characters\Jobs\HandlePoseTrainingWebhook;
use App\Syllaby\Characters\Jobs\HandleCharacterFinalTrainingWebhook;

class CustomCharacterWebhookController extends Controller
{
    public function handle(Request $request, string $type): JsonResponse
    {
        if (in_array($request->input('status'), ['processing', 'queued'])) {
            return $this->success();
        }

        match (strtolower($type)) {
            'poses' => $this->handlePoseTraining($request),
            'final' => $this->handleFinalTraining($request),
        };

        return $this->success();
    }

    private function handlePoseTraining(Request $request): void
    {
        $character = $this->fetchCharacter($request->query('character'));

        if (is_null($character)) {
            Log::error('Character not found for pose training webhook', [
                'character' => $request->query('character'),
                'request' => $request->except('logs'),
            ]);

            return;
        }

        dispatch(new HandlePoseTrainingWebhook($character, $request->except('logs')));
    }

    private function handleFinalTraining(Request $request): void
    {
        $character = $this->fetchCharacter($request->query('character'));

        if (is_null($character)) {
            Log::error('Character not found for final training webhook', [
                'character' => $request->query('character'),
                'request' => $request->except('logs'),
            ]);

            return;
        }

        dispatch(new HandleCharacterFinalTrainingWebhook($character, $request->except('logs')));
    }

    private function fetchCharacter(?string $character = null): ?Character
    {
        if (is_null($character)) {
            return null;
        }

        return Character::query()->find($character);
    }

    private function success(): JsonResponse
    {
        return new JsonResponse('Webhook Handled', 200);
    }
}
