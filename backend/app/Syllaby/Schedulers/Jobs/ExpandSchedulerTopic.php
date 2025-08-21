<?php

namespace App\Syllaby\Schedulers\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Support\Sleep;
use App\System\Enums\QueueType;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Foundation\Queue\Queueable;
use App\Syllaby\Generators\DTOs\ChatConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Prompts\ExpandTopicPrompt;
use App\System\Jobs\Middleware\SwitchesManagersDrivers;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;

class ExpandSchedulerTopic implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    private const int MAX_ATTEMPTS = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Scheduler $scheduler, protected int $amount)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $generator = $this->scheduler->generator;

        if (! $titles = $this->expand($generator, $this->amount)) {
            $this->release(30);

            return;
        }

        $generator->update(['context' => $titles]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        // event(new FacelessGenerationFailed($this->faceless));
    }

    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return count(Chat::getFacadeRoot()->getAvailableDrivers()) * 5;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->scheduler->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["expand-scheduler-topic:{$this->scheduler->id}"];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(5, 2))->backoff(2),
            (new SwitchesManagersDrivers(5))->using(Chat::getFacadeRoot())->by('chat-attempts'),
        ];
    }

    /**
     * Attempts to expand the main topic with the given amount of subtopics.
     */
    private function expand(Generator $generator, int $amount, int $attempts = self::MAX_ATTEMPTS): ?array
    {
        if ($attempts <= 0) {
            return null;
        }

        $chat = Chat::driver('gpt');

        $config = match (Chat::getFacadeRoot()->getCurrentDriver()) {
            'gpt' => new ChatConfig(responseFormat: config('openai.json_schemas.expand-topic')),
            default => null,
        };

        $response = $chat->send(ExpandTopicPrompt::build($generator, $amount), $config);

        if (! json_validate($response->text)) {
            Sleep::for(2)->seconds();

            return $this->expand($generator, $amount, $attempts - 1);
        }

        $titles = Arr::get(json_decode($response->text, true), 'titles', []);

        if (count($titles) !== $amount) {
            Sleep::for(2)->seconds();

            return $this->expand($generator, $amount, $attempts - 1);
        }

        return $titles;
    }
}
