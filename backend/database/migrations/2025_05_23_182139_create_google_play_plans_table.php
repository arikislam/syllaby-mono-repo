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
        Schema::create('google_play_plans', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique()->comment('Google Play product ID');
            $table->enum('product_type', ['subscription', 'inapp']);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');

            // For subscriptions
            $table->string('base_plan_id')->nullable();
            $table->string('billing_period', 50)->nullable();

            // Pricing
            $table->bigInteger('price_micros');
            $table->string('currency_code', 3)->default('USD');

            // Credits and features
            $table->json('features')->nullable();

            // Relationship to Stripe plans
            $table->unsignedBigInteger('plan_id')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_play_plans');
    }
};
