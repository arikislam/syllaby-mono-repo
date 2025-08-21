<?php

namespace App\Http\Controllers\Api\v1\Metadata;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiCompletionsResource;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Http\Requests\Metadata\GenerateMetadataRequest;
use App\Syllaby\Publisher\Metadata\Prompts\TitlePrompt;

class GenerateTitleController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(GenerateMetadataRequest $request): JsonResponse
    {
        try {
            $prompt = TitlePrompt::generate($request->input('context'), $request->input('provider'));

            $response = Chat::driver('gpt')->send($prompt);

            return $this->respondWithResource(AiCompletionsResource::make($response->text));
        } catch (Throwable $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }
}
