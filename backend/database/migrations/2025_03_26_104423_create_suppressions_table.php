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
        Schema::create('suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id')->nullable();
            $table->string('email')->unique();
            $table->text('reason')->nullable();
            $table->string('soft_bounce_count')->default(0);
            $table->string('bounce_type')->nullable();
            $table->dateTime('bounced_at')->nullable();
            $table->dateTime('complained_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'bounced_at', 'complained_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppressions');
    }
};
