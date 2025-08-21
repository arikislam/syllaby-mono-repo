<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('google_play_subscriptions', function (Blueprint $table) {
            // Add offer_id after product_id
            $table->string('offer_id')->nullable()->after('product_id');

            // Add trial period and intro price info after status
            $table->json('free_trial_period')->nullable()->after('status');
            $table->json('intro_price_info')->nullable()->after('free_trial_period');

            // Add trial timestamps after existing timestamps
            $table->timestamp('trial_start_at')->nullable()->after('grace_period_expires_at');
            $table->timestamp('trial_end_at')->nullable()->after('trial_start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_play_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'offer_id',
                'free_trial_period',
                'intro_price_info',
                'trial_start_at',
                'trial_end_at',
            ]);
        });
    }
};
