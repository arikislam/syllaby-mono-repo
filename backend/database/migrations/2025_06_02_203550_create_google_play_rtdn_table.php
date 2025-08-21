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
        Schema::create('google_play_rtdn', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('purchase_token', 512);
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('message_id', 255)->nullable()->unique();
            $table->string('notification_type', 100)->nullable();
            $table->json('rtdn_response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->tinyInteger('status')->default(0); // 0 = pending, 1 = processed, 2 = failed
            $table->json('processing_errors')->nullable();
            $table->boolean('google_api_verified')->default(false);
            $table->json('google_api_response')->nullable();
            $table->timestamps();

            // Allow multiple records per purchase_token to track subscription lifecycle
            // No unique constraint - multiple events per purchase token are allowed
            
            $table->index('status');
            $table->index('notification_type');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index(['purchase_token', 'created_at'], 'google_play_rtdn_token_created_idx');
            $table->index(['purchase_token', 'notification_type', 'created_at'], 'google_play_rtdn_lifecycle_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_play_rtdn');
    }
};
