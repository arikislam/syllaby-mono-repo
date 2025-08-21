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
        Schema::create('google_play_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('purchase_token')->unique();
            $table->string('order_id')->nullable();
            $table->string('product_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('status', 50)->default('purchased');
            $table->timestamp('purchased_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->bigInteger('price_amount_micros');
            $table->string('price_currency_code', 3);
            $table->string('country_code', 2)->nullable();
            $table->integer('purchase_state')->nullable();
            $table->integer('consumption_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('product_id');
            $table->index('order_id');
            $table->index('purchased_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_play_purchases');
    }
};
