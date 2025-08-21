<?php

namespace App\Syllaby\Publisher\Publications;

use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

class PublicationLog extends Model
{
    protected $fillable = [
        'publication_id',
        'social_channel_id',
        'provider',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => SocialUploadStatus::class,
        ];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(SocialChannel::class, 'social_channel_id');
    }
}
