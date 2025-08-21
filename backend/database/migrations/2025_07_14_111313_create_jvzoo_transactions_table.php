<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Syllaby\Subscriptions\Enums\JVZooPaymentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jvzoo_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('jvzoo_subscription_id')->nullable()->constrained('jvzoo_subscriptions')->onDelete('set null');

            $table->string('receipt')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_type')->nullable();
            $table->string('transaction_type')->nullable();
            $table->integer('amount')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('vendor')->nullable();
            $table->string('affiliate')->nullable();

            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_state', 2)->nullable();
            $table->string('customer_country', 2)->nullable();
            $table->string('upsell_receipt')->nullable();
            $table->string('affiliate_tracking_id', 24)->nullable();

            $table->text('vendor_through')->nullable();
            $table->string('verification_hash', 8)->nullable();

            $table->string('status')->default(JVZooPaymentStatus::PENDING->value);
            $table->timestamp('verified_at')->nullable();

            $table->string('onboarding_token', 64)->nullable()->unique();
            $table->timestamp('onboarding_expires_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->json('referral_metadata')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jvzoo_transactions');
    }
};
