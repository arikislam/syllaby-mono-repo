<?php

namespace App\Syllaby\Generators\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;

class GenerateFacelessScriptAction
{
    public function __construct(protected WriteScriptAction $writer) {}

    public function handle(Faceless $faceless, array $input): ?Faceless
    {
        $generator = $faceless->generator()->updateOrCreate([], Arr::except($input, 'duration'));

        if (! $response = $this->writer->handle($generator, $input)) {
            return null;
        }

        return attempt(function () use ($faceless, $generator, $response, $input) {
            $this->charge($faceless->user, $generator, $faceless, $response);

            $faceless->video()->update([
                'status' => VideoStatus::DRAFT,
                'title' => Str::limit($generator->topic, 255, ''),
            ]);

            return tap($faceless)->update([
                'is_transcribed' => false,
                'script' => $response->text,
                'estimated_duration' => Arr::get($input, 'duration', 0),
            ]);
        });
    }

    private function charge(User $user, Generator $generator, Faceless $faceless, ChatResponse $response): void
    {
        (new CreditService($user))->decrement(
            type: CreditEventEnum::CONTENT_PROMPT_REQUESTED,
            creditable: $generator,
            amount: $response->completionTokens,
            meta: ['content_type' => $faceless->getMorphClass()],
            label: Str::limit($response->text, CreditHistory::TRUNCATED_LENGTH)
        );
    }
}
