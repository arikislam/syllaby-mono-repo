<?php

namespace App\Syllaby\Loggers\Services;

use App\Syllaby\Loggers\VideoLog;
use App\Syllaby\Loggers\DTOs\VideoLogData;

class VideoLogger
{
    public function __construct(protected VideoLogData $model)
    {
        //
    }

    /**
     *
     */
    public static function record(VideoLogData $model): self
    {
        return new self($model);
    }

    /*
     *
     */
    public function start(): void
    {
        VideoLog::create([
            'video_id' => $this->model->id,
            'status' => $this->model->status,
            'provider' => $this->model->provider,
            'render_duration' => null,
            'render_finished_at' => null,
            'render_started_at' => now(),
        ]);
    }

    /**
     * @return void
     */
    public function end(): void
    {
        $log = VideoLog::firstOrNew([
            'video_id' => $this->model->id,
            'provider' => $this->model->provider,
        ]);

        $log->fill([
            'render_duration' => $this->duration($log),
            'status' => $this->model->status,
            'render_finished_at' => $this->model->synced_at ?? now(),
        ])->save();
    }

    /**
     * Calculate the amount of time the given video took to render.
     */
    private function duration(VideoLog $log): int
    {
        $started = $log->render_started_at ?? now();
        $finished = $this->model->synced_at ?? now();

        return (int) $started->diffInSeconds($finished);
    }
}
