<?php

namespace App\Syllaby\Publisher\Publications;

use App\Syllaby\Publisher\Channels\SocialChannel;
use Database\Factories\AccountPublicationFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class AccountPublication extends Pivot
{
    use HasFactory;

    protected $table = 'account_publications';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'post_type' => PostType::class,
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

    public function provider(): string
    {
        return $this->channel->account->provider->toString();
    }

    public function name(): string
    {
        return match ($this->channel->account->provider) {
            SocialAccountEnum::TikTok, SocialAccountEnum::LinkedIn, SocialAccountEnum::Facebook, SocialAccountEnum::Instagram, SocialAccountEnum::Threads => $this->metadata['caption'] ?? '',
            SocialAccountEnum::Youtube => $this->metadata['title'] ?? '',
            default => '',
        };
    }

    protected static function newFactory(): Factory
    {
        return AccountPublicationFactory::new();
    }
}
