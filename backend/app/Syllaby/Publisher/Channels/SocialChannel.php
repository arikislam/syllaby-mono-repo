<?php

namespace App\Syllaby\Publisher\Channels;

use App\Syllaby\Users\User;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Database\Factories\SocialChannelFactory;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Syllaby\Publisher\Publications\AccountPublication;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use App\Syllaby\Publisher\Metrics\Traits\HasMetricAggregates;

class SocialChannel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use HasMetricAggregates;

    const string ORGANIZATION = 'organization';

    const string INDIVIDUAL = 'individual';

    const string PAGE = 'page';

    const string PROFESSIONAL_ACCOUNT = 'professional_account';

    protected $guarded = [];

    protected $hidden = ['access_token'];

    /**
     * Channel Social Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, SocialAccount::class, 'id', 'id', 'social_account_id', 'user_id');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'account_publications')
            ->using(AccountPublication::class)
            ->withPivot('status', 'post_type', 'metadata', 'error_message', 'provider_media_id')
            ->withTimestamps();
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(PublicationMetricValue::class);
    }

    public function scopeMetricsFor(EloquentBuilder $query, string $type, Publication $publication, PublicationMetricKey $key): void
    {
        $query->addSelect([$type => function (Builder $query) use ($publication, $key) {
            $query->selectRaw('CAST(value as unsigned)')
                ->from('publication_metric_values')
                ->where('publication_id', $publication->id)
                ->whereColumn('social_channel_id', 'social_channels.id')
                ->where('publication_metric_key_id', $key->id)
                ->latest()
                ->limit(1);
        }]);
    }

    public function isOrganization(): bool
    {
        return $this->type === self::ORGANIZATION;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    protected static function newFactory(): Factory
    {
        return SocialChannelFactory::new();
    }
}
