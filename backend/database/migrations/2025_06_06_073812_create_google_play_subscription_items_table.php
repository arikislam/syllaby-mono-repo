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
        Schema::create('google_play_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('google_play_subscription_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('product_id');
            $table->integer('quantity')->default(1);
            $table->bigInteger('price_amount_micros');
            $table->string('price_currency_code', 3);
            $table->string('billing_period')->nullable();
            $table->timestamps();

            $table->index('google_play_subscription_id', 'gp_subscription_id_index');
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_play_subscription_items');
    }
};
