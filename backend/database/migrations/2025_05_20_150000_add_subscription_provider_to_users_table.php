<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

class AddSubscriptionProviderToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // After stripe_id to keep payment-related columns grouped
            $table->unsignedTinyInteger('subscription_provider')
                ->default(SubscriptionProvider::STRIPE->value)
                ->after('stripe_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subscription_provider');
        });
    }
}
