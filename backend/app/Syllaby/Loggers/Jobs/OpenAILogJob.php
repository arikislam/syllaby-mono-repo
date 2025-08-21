<?php

namespace App\Syllaby\Loggers\Jobs;

use Exception;
use Illuminate\Support\Arr;
use App\Syllaby\Loggers\OpenAiLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class OpenAILogJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly array $request, private readonly array $response) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (blank($this->request) || blank($this->response)) {
            return;
        }

        try {
            $txt = Arr::get($this->response, 'choices.0.message.content');
            $responseText = $txt ? trim(preg_replace('/\s\s+/', ' ', $txt)) : '';

            OpenAiLog::create([
                'request_prompt' => Arr::get($this->request, 'messages.0.content'),
                'request_model' => Arr::get($this->request, 'model'),
                'request_max_tokens' => Arr::get($this->request, 'max_completion_tokens'),
                'request_temperature' => Arr::get($this->request, 'temperature', 1),
                'request_top_p' => Arr::get($this->request, 'top_p'),
                'request_frequency_penalty' => Arr::get($this->request, 'frequency_penalty'),
                'request_presence_penalty' => Arr::get($this->request, 'presence_penalty'),
                'response_id' => Arr::get($this->response, 'id'),
                'response_text' => $responseText,
                'response_finish_reason' => Arr::get($this->response, 'choices.0.finish_reason'),
                'response_prompt_tokens' => Arr::get($this->response, 'usage.prompt_tokens'),
                'response_completion_tokens' => Arr::get($this->response, 'usage.completion_tokens'),
                'response_total_tokens' => Arr::get($this->response, 'usage.total_tokens'),
            ]);
        } catch (Exception $exception) {
            Log::error('openai-log', [$exception->getMessage()]);
        }
    }
}
