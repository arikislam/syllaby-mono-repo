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
        Schema::create('jvzoo_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jvzoo_subscription_id')->constrained('jvzoo_subscriptions')->onDelete('cascade');
            $table->foreignId('jvzoo_plan_id')->constrained('jvzoo_plans')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['jvzoo_subscription_id', 'jvzoo_plan_id'], 'jvzoo_sub_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jvzoo_subscription_items');
    }
};
