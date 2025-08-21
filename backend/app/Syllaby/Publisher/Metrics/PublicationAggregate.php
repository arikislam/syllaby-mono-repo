<?php

namespace App\Syllaby\Publisher\Metrics;

use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Database\Factories\PublicationAggregateFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicationAggregate extends Model
{
    /** @use HasFactory<PublicationAggregateFactory> */
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'social_channel_id',
        'key',
        'value',
        'type',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'last_updated_at' => 'datetime',
        ];
    }

    /**
     * Get the publication that owns the aggregate.
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    /**
     * Get the social channel that owns the aggregate.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(SocialChannel::class, 'social_channel_id');
    }
}
