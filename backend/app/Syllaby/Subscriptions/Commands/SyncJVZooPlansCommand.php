<?php

namespace App\Syllaby\Subscriptions\Commands;

use Illuminate\Console\Command;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\JVZooPlan;

class SyncJVZooPlansCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:jvzoo';

    /**
     * The console command description.
     */
    protected $description = 'Sync JVZoo plans with Stripe plans';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (! $mapping = config('services.jvzoo.plans', null)) {
            $this->warn('No JVZoo plans found in config/services.php');

            return;
        }

        if (! $plans = Plan::whereIn('plan_id', array_values($mapping))->first()) {
            $this->warn('No plans found in Stripe');

            return;
        }

        $plans->each(function ($plan) use ($mapping) {
            if (! $jvzooId = array_search($plan->plan_id, $mapping)) {
                return;
            }

            JVZooPlan::updateOrCreate(['jvzoo_id' => $jvzooId, 'plan_id' => $plan->id], [
                'name' => $plan->name,
                'price' => $plan->price,
                'currency' => $plan->currency ?? 'usd',
                'is_active' => $plan->active,
                'interval' => $plan->type === 'one_time' ? null : $plan->type,
                'metadata' => [
                    'full_credits' => $plan->meta['full_credits'],
                    'trial_credits' => $plan->meta['trial_credits'],
                ],
            ]);
        });

        $this->info('JVZoo plans sync completed');
    }
}
