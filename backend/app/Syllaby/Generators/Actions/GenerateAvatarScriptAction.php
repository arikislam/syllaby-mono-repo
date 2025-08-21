<?php

namespace App\Syllaby\Generators\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Generators\Generator;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Generators\Prompts\ScriptPrompt;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\Generators\Vendors\Assistants\Chat;

class GenerateAvatarScriptAction
{
    /**
     * Handles the real clone script generation process.
     */
    public function handle(RealClone $clone, array $input): ?RealClone
    {
        $user = $clone->user;
        $generator = $this->record($clone, $input);

        $response = Chat::driver('gpt')->send(ScriptPrompt::build($generator));

        if (blank($response->text)) {
            return $this->fail($clone);
        }

        return DB::transaction(function () use ($clone, $user, $generator, $response) {
            $this->charge($user, $clone, $generator, $response);

            return tap($clone)->update([
                'status' => RealCloneStatus::DRAFT,
                'script' => $response->text,
            ]);
        });
    }

    /**
     * Record the latest data changes for the real clone script.
     */
    private function record(RealClone $clone, array $input): Generator
    {
        return $clone->generator()->updateOrCreate(
            ['model_id' => $clone->id, 'model_type' => $clone->getMorphClass()],
            $this->values($input)
        );
    }

    /**
     * Values to be updated for the real clone script.
     */
    private function values(array $input): array
    {
        return [
            'tone' => Arr::get($input, 'tone'),
            'topic' => Arr::get($input, 'topic'),
            'style' => Arr::get($input, 'style'),
            'length' => Arr::get($input, 'length'),
            'language' => Arr::get($input, 'language'),
        ];
    }

    /**
     * Charge the user credits for the generated real clone script.
     */
    private function charge(User $user, RealClone $clone, Generator $generator, ChatResponse $response): void
    {
        (new CreditService($user))->decrement(
            type: CreditEventEnum::CONTENT_PROMPT_REQUESTED,
            creditable: $generator,
            amount: $response->completionTokens,
            meta: ['content_type' => $clone->getMorphClass()],
            label: Str::limit($response->text, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    /**
     * Handle OpenAI failed response.
     */
    private function fail(RealClone $clone)
    {
        Log::error('OpenAI script generation failed for real clone {id}', ['id' => $clone->id]);

        return null;
    }
}
