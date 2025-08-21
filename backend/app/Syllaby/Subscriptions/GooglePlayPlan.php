<?php

namespace App\Syllaby\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GooglePlayPlan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'google_play_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'product_type',
        'name',
        'description',
        'status',
        'base_plan_id',
        'billing_period',
        'price_micros',
        'currency_code',
        'features',
        'plan_id',
        'metadata',
        'offers',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'metadata' => 'array',
        'offers' => 'array',
        'price_micros' => 'integer',
        'plan_id' => 'integer',
    ];

    /**
     * Get the price in dollars
     */
    public function getPriceInDollarsAttribute(): float
    {
        return $this->price_micros / 1000000;
    }

    /**
     * Set the price in dollars
     */
    public function setPriceInDollarsAttribute(float $value): void
    {
        $this->attributes['price_micros'] = $value * 1000000;
    }

    /**
     * Get the Stripe plan associated with this Google Play plan
     */
    public function stripePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Scope to filter active plans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter subscription products
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('product_type', 'subscription');
    }

    /**
     * Scope to filter in-app products
     */
    public function scopeInApp($query)
    {
        return $query->where('product_type', 'inapp');
    }

    /**
     * Check if this is a subscription product
     */
    public function isSubscription(): bool
    {
        return $this->product_type === 'subscription';
    }

    /**
     * Check if this is an in-app purchase product
     */
    public function isInApp(): bool
    {
        return $this->product_type === 'inapp';
    }

    /**
     * Get formatted price string
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$'.number_format($this->price_in_dollars, 2);
    }

    /**
     * Get Google Play title
     */
    public function getGooglePlayTitle(): string
    {
        if (isset($this->metadata['listings']['en-US']['title'])) {
            return $this->metadata['listings']['en-US']['title'];
        }

        return $this->name;
    }

    /**
     * Get Google Play description
     */
    public function getGooglePlayDescription(): string
    {
        if (isset($this->metadata['listings']['en-US']['description'])) {
            return $this->metadata['listings']['en-US']['description'];
        }

        return '';
    }

    /**
     * Generate Google Play SKU from plan data
     */
    public function generateGooglePlaySku(Plan $plan): string
    {
        // Create SKU based on Stripe plan_id and type
        $prefix = $plan->type === 'product' ? 'product' : 'price';

        // Clean ID - only lowercase alphanumeric chars allowed in Google Play SKUs
        $cleanId = strtolower(preg_replace('/[^a-z0-9]/', '', $plan->plan_id));

        // Make sure it's not too long (Google Play has a limit)
        if (strlen($cleanId) > 40) {
            $cleanId = substr($cleanId, 0, 36).substr(md5($cleanId), 0, 4);
        }

        // Ensure it's never empty
        if (empty($cleanId)) {
            $cleanId = 'plan'.$plan->id;
        }

        return "{$prefix}{$cleanId}";
    }

    /**
     * Convert price from Stripe cents to Google Play micros
     */
    public function convertPriceToMicros(int $priceInCents): int
    {
        // Convert from Stripe cents to Google Play micros
        return (int) ($priceInCents * 10000); // $1.00 (100 cents) = 1,000,000 micros
    }

    /**
     * Mark plan as synced with Google Play
     */
    public function markAsSynced(): void
    {
        $this->touch();
    }

    /**
     * Check if this GooglePlay plan needs update compared to its Stripe plan
     */
    public function needsUpdate(): bool
    {
        if (! $this->stripePlan) {
            return false;
        }

        // Check if the Stripe plan was updated after this Google Play plan
        return $this->stripePlan->updated_at > $this->updated_at;
    }

    /**
     * Get active offers for this plan.
     */
    public function getActiveOffers(): array
    {
        if (empty($this->offers)) {
            return [];
        }

        return array_filter($this->offers, function ($offer) {
            return ($offer['state'] ?? '') === 'ACTIVE';
        });
    }

    /**
     * Get trial offers for this plan.
     */
    public function getTrialOffers(): array
    {
        $activeOffers = $this->getActiveOffers();

        return array_filter($activeOffers, function ($offer) {
            $phases = $offer['phases'] ?? [];

            foreach ($phases as $phase) {
                if ($phase['is_trial'] ?? false) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Get intro pricing offers for this plan.
     */
    public function getIntroPricingOffers(): array
    {
        $activeOffers = $this->getActiveOffers();

        return array_filter($activeOffers, function ($offer) {
            $phases = $offer['phases'] ?? [];

            foreach ($phases as $phase) {
                if ($phase['is_intro_pricing'] ?? false) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Check if a specific offer ID exists and is active.
     */
    public function hasActiveOffer(string $offerId): bool
    {
        $activeOffers = $this->getActiveOffers();

        foreach ($activeOffers as $offer) {
            if (($offer['offer_id'] ?? '') === $offerId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get offer details by offer ID.
     */
    public function getOfferById(string $offerId): ?array
    {
        if (empty($this->offers)) {
            return null;
        }

        foreach ($this->offers as $offer) {
            if (($offer['offer_id'] ?? '') === $offerId) {
                return $offer;
            }
        }

        return null;
    }

    /**
     * Check if this plan has any trial offers.
     */
    public function hasTrialOffers(): bool
    {
        return ! empty($this->getTrialOffers());
    }

    /**
     * Check if this plan has any intro pricing offers.
     */
    public function hasIntroPricingOffers(): bool
    {
        return ! empty($this->getIntroPricingOffers());
    }

    /**
     * Get offer tags for a specific offer.
     */
    public function getOfferTags(string $offerId): array
    {
        $offer = $this->getOfferById($offerId);

        return $offer['offer_tags'] ?? [];
    }

    /**
     * Check if an offer is a trial offer.
     */
    public function isTrialOffer(string $offerId): bool
    {
        $offer = $this->getOfferById($offerId);

        if (! $offer) {
            return false;
        }

        $phases = $offer['phases'] ?? [];

        foreach ($phases as $phase) {
            if ($phase['is_trial'] ?? false) {
                return true;
            }
        }

        return false;
    }
}
