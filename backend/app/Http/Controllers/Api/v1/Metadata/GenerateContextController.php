<?php

namespace App\Http\Controllers\Api\v1\Metadata;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiCompletionsResource;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Publisher\Metadata\Prompts\ContextPrompt;

class GenerateContextController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['text' => ['required', 'string']]);

        $prompt = ContextPrompt::generate($request->input('text'));

        $response = Chat::driver('gpt')->send($prompt);

        return $this->respondWithResource(AiCompletionsResource::make($response->text));
    }
}
