<?php

namespace App\Syllaby\Publisher\Metrics;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\PublicationMetricValueFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicationMetricValue extends Model
{
    use HasFactory, MassPrunable;

    protected $fillable = [
        'publication_id',
        'social_channel_id',
        'publication_metric_key_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    protected $hidden = [
        'updated_at',
    ];

    protected $with = ['key:id,slug'];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'publication_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(SocialChannel::class, 'social_channel_id');
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(PublicationMetricKey::class, 'publication_metric_key_id');
    }

    public function prunable(): Builder
    {
        return $this->query()->where('created_at', '<', now()->subMonths(6));
    }

    protected static function newFactory(): Factory
    {
        return PublicationMetricValueFactory::new();
    }
}
