<?php

namespace App\Syllaby\Publisher\Channels;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class SocialAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'provider' => SocialAccountEnum::class,
            'needs_reauth' => 'boolean',
        ];
    }

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $appends = [
        'icon',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(SocialChannel::class);
    }

    public function icon(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->provider) {
            SocialAccountEnum::Youtube => asset('assets/social-logos/youtube.svg'),
            SocialAccountEnum::TikTok => asset('assets/social-logos/tiktok.svg'),
            SocialAccountEnum::LinkedIn => asset('assets/social-logos/linkedin.svg'),
            SocialAccountEnum::Facebook => asset('assets/social-logos/facebook.svg'),
            SocialAccountEnum::Instagram => asset('assets/social-logos/instagram.svg'),
            SocialAccountEnum::Threads => asset('assets/social-logos/threads.svg'),
            default => '',
        });
    }

    public function setNeedsReauth(bool $value): self
    {
        $this->needs_reauth = $value;

        return $this;
    }

    protected static function newFactory(): Factory
    {
        return SocialAccountFactory::new();
    }
}
