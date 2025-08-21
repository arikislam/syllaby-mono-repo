<?php

namespace App\Syllaby\Loggers;

use App\Syllaby\Videos\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'video_generator_logs';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'render_started_at' => 'datetime',
            'render_finished_at' => 'datetime',
        ];
    }

    /**
     * Gets the corresponding logged video.
     */
    public function videos(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
