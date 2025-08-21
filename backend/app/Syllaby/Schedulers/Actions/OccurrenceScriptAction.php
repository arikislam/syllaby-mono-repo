<?php

namespace App\Syllaby\Schedulers\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Schedulers\Occurrence;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Generators\Actions\WriteScriptAction;

class OccurrenceScriptAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(protected WriteScriptAction $writer) {}

    /**
     * Handle the update of an occurrence.
     *
     * @throws Exception
     */
    public function handle(Occurrence $occurrence, User $user, array $input = []): Occurrence
    {
        $topic = Arr::get($input, 'topic', $occurrence->topic);

        $generator = tap($occurrence->generator)->update([
            'topic' => $topic,
        ]);

        if (! $response = $this->writer->handle($generator)) {
            throw new Exception(__('videos.script_failed'));
        }

        $this->charge($user, $occurrence, $generator, $response);

        return tap($occurrence)->update([
            'topic' => $topic,
            'script' => $response->text,
        ]);
    }

    /**
     * Charge the user for the script generation.
     */
    private function charge(User $user, Occurrence $occurrence, Generator $generator, ChatResponse $response): void
    {
        (new CreditService($user))->decrement(
            type: CreditEventEnum::CONTENT_PROMPT_REQUESTED,
            creditable: $generator,
            amount: $response->completionTokens,
            meta: ['content_type' => $occurrence->getMorphClass()],
            label: Str::limit($response->text, CreditHistory::TRUNCATED_LENGTH)
        );
    }
}
