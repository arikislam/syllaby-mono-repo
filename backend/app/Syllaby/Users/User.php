<?php

namespace App\Syllaby\Users;

use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Video;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Surveys\Answer;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\Surveys\Industry;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use App\Syllaby\Ideas\KeywordUser;
use App\Syllaby\Subscriptions\Plan;
use Database\Factories\UserFactory;
use App\System\Traits\HasActiveFlag;
use App\Syllaby\Characters\Character;
use App\Syllaby\Subscriptions\Coupon;
use App\Syllaby\Users\Enums\UserType;
use App\System\Contracts\Activatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\Subscriptions\Traits\Billable;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Users\Preferences\HasPreferences;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Auth\Extensions\CanResetPassword as CustomCanResetPassword;

class User extends Authenticatable implements Activatable, HasMedia
{
    use Billable;
    use CustomCanResetPassword;
    use HasActiveFlag;
    use HasApiTokens;
    use HasFactory;
    use HasPreferences;
    use InteractsWithMedia;
    use Notifiable;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'password' => 'hashed',
            'active' => 'boolean',
            'pm_exempt' => 'boolean',
            'notifications' => 'array',
            'ad_tracking' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'provider' => SocialAccountEnum::class,
            'subscription_provider' => SubscriptionProvider::class,
        ];
    }

    /**
     * Get the user's name.
     */
    public function name(): Attribute
    {
        return Attribute::get(fn ($name) => ucfirst($name));
    }

    /**
     * Get the user's source platform (web, ios, android).
     */
    public function source(): Attribute
    {
        return Attribute::get(function () {
            return $this->subscription_provider
                ? $this->subscription_provider->toSource()
                : SubscriptionProvider::SOURCE_WEB;
        });
    }

    public function getSubscriptionLeftDaysAttribute(): ?int
    {
        if (blank($this->subscriptions) || blank($endDate = $this->subscription()->ends_at)) {
            return null;
        }

        return now()->diffInDays($endDate);
    }

    /**
     * Get all the user keywords search history.
     */
    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class)
            ->using(KeywordUser::class)
            ->withTimestamps()
            ->as('history');
    }

    /**
     * Get the users answers.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Get user industries
     */
    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class);
    }

    /**
     * Get the user current plan.
     */
    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id');
    }

    /**
     * Get all coupons redeemed by the user.
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'redemptions')
            ->as('redemptions')
            ->withTimestamps();
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function speeches(): HasMany
    {
        return $this->hasMany(Speech::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function owns(Model $model, string $key = ''): bool
    {
        $key = filled($key) ? $key : $this->getForeignKey();

        return $this->getKey() === $model->{$key};
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'user_id');
    }

    public function getShowWelcomeModalAttribute(): bool
    {
        if (! $this->subscribed()) {
            return false;
        }

        if (blank(data_get($this->settings, 'welcome_message'))) {
            return true;
        }

        return false;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('welcome-video')->singleFile();
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->user_type === UserType::ADMIN->value;
    }

    /**
     * Get the total credits of the user.
     */
    public function credits(): int
    {
        return ($this->remaining_credit_amount ?? 0) + $this->extra_credits;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
