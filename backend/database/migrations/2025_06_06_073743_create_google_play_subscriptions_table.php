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
        Schema::create('google_play_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('subscription_id')->unique()->comment('Google Play subscription ID');
            $table->string('purchase_token')->unique();
            $table->string('linked_purchase_token')->nullable()->comment('Previous purchase token for plan changes');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('product_id')->comment('Google Play product ID');
            $table->string('status', 50)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('grace_period_expires_at')->nullable();
            $table->boolean('auto_renewing')->default(true);
            $table->string('payment_state', 50)->nullable();
            $table->string('price_currency_code', 3)->nullable();
            $table->bigInteger('price_amount_micros')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->json('acknowledgement_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('expires_at');
            $table->index('product_id');
            $table->index('linked_purchase_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_play_subscriptions');
    }
};
