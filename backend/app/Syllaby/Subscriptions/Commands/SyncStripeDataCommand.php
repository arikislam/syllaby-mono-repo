<?php

namespace App\Syllaby\Subscriptions\Commands;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\Coupon;
use App\Syllaby\Subscriptions\Services\SubscriptionService;

class SyncStripeDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:stripe';

    /**
     * The console command description.
     */
    protected $description = 'Sync Stripe plans and coupons with local database';

    /**
     * Stripe payments gateway.
     */
    protected SubscriptionService $stripe;

    /**
     * Create a new command instance.
     */
    public function __construct(SubscriptionService $stripe)
    {
        parent::__construct();

        $this->stripe = $stripe;
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $this->syncProducts();
        $this->syncCoupons();
    }

    /**
     * Sync prices from stripe into storage.
     *
     * @throws Exception
     */
    private function syncProducts(): void
    {
        if (!$products = $this->stripe->getAllProducts()) {
            return;
        }

        foreach ($products as $product) {
            $product = $this->createProduct($product);
            $this->info("Product $product->plan_id synced");

            if (!$prices = $this->stripe->getAllPrices($product->plan_id)) {
                continue;
            }

            foreach ($prices as $price) {
                $price = $this->createPrice($price, $product);
                $this->info("Price $price->plan_id synced");
            }
        }
    }

    /**
     * Syncs coupons from Stripe into storage.
     *
     * @throws Exception
     */
    public function syncCoupons(): bool
    {
        try {
            if (!$coupons = $this->stripe->getAllCoupons()) {
                return false;
            }
        } catch (Exception $exception) {
            return tap(false, fn () => Log::error($exception->getMessage()));
        }

        collect($coupons)->map($this->mapCouponFields())->each(function ($coupon) {
            Coupon::updateOrCreate(
                ['code' => $code = Arr::get($coupon, 'code')],
                Arr::except($coupon, ['code'])
            );

            $this->info("Coupon updated: $code");
        });

        return true;
    }

    /**
     * Syncs the product from Stripe
     */
    private function createProduct(array $product): Plan
    {
        $fields = [
            'plan_id' => Arr::get($product, 'id'),
            'name' => Arr::get($product, 'name') ?? 'not set',
            'type' => Arr::get($product, 'object'),
            'slug' => Str::slug(Arr::get($product, 'name') ?? 'not-set'),
            'meta' => Arr::get($product, 'metadata'),
            'active' => (int) Arr::get($product, 'active'),
        ];

        return Plan::updateOrCreate(
            ['plan_id' => Arr::get($fields, 'plan_id')],
            Arr::except($fields, ['plan_id'])
        );
    }

    /**
     * Syncs the price from Stripe
     */
    private function createPrice(array $price, Plan $product): Plan
    {
        $fields = [
            'parent_id' => $product->id,
            'name' => Arr::get($price, 'nickname') ?? 'not set',
            'plan_id' => Arr::get($price, 'id'),
            'currency' => Arr::get($price, 'currency'),
            'type' => $this->resolvePriceType($price),
            'price' => Arr::get($price, 'unit_amount'),
            'slug' => Str::slug(Arr::get($price, 'lookup_key') ?? 'not-set'),
            'meta' => Arr::get($price, 'metadata'),
            'active' => (int) Arr::get($price, 'active'),
        ];

        return Plan::updateOrCreate(
            ['plan_id' => Arr::get($fields, 'plan_id')],
            Arr::except($fields, ['plan_id'])
        );
    }

    /**
     * Maps stripe coupons object to storage fields.
     *
     * @return callable
     */
    private function mapCouponFields(): callable
    {
        return fn (array $coupon) => [
            'name' => Arr::get($coupon, 'name'),
            'code' => Arr::get($coupon, 'id'),
            'duration' => Arr::get($coupon, 'duration'),
            'duration_in_months' => Arr::get($coupon, 'duration_in_months'),
            'max_redemptions' => Arr::get($coupon, 'max_redemptions'),
            'amount_off' => Arr::get($coupon, 'amount_off'),
            'percent_off' => Arr::get($coupon, 'percent_off'),
            'meta' => Arr::get($coupon, 'metadata'),
        ];
    }

    /**
     * Get the recurring type of price.
     */
    private function resolvePriceType(array $price): string
    {
        if (Arr::get($price, 'type') === 'one_time') {
            return Arr::get($price, 'type');
        }

        return Arr::get($price, 'recurring.interval');
    }
}
