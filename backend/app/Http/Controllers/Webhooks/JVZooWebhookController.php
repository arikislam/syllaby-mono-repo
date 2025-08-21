<?php

namespace App\Http\Controllers\Webhooks;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\Actions\JVZooRenewalAction;
use App\Syllaby\Subscriptions\Enums\JVZooTransactionType;
use App\Syllaby\Subscriptions\Actions\JVZooPlanSwapAction;
use App\Syllaby\Subscriptions\Actions\JVZooOnboardingAction;
use App\Syllaby\Subscriptions\Actions\JVZooCancellationAction;
use App\Syllaby\Subscriptions\Actions\JVZooPaymentFailureAction;
use App\Syllaby\Subscriptions\Actions\JVZooTrialConversionAction;
use App\Syllaby\Subscriptions\Actions\ProcessJVZooTransactionAction;

class JVZooWebhookController extends Controller
{
    /**
     * New controller instance.
     */
    public function __construct(private ProcessJVZooTransactionAction $transaction)
    {
        $this->middleware('verify.jvzoo');
    }

    /**
     * Handle JVZoo webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $type = Arr::get($payload, 'ctransaction');
        if (! $type = JVZooTransactionType::tryFrom($type)) {
            return response()->json(['message' => 'Invalid transaction type'], 400);
        }

        $receipt = Arr::get($payload, 'ctransreceipt');
        if ($this->isDuplicateTransaction($receipt, $type)) {
            return response()->json(['message' => 'Duplicate JVZoo transaction received'], 200);
        }

        $user = $this->getJVZooUser($payload);
        $record = $this->transaction->handle($payload);

        if (blank($user) && $this->onboardUser($type, $user)) {
            $this->handleOnboarding($record, $payload);

            return response()->json(['message' => 'Success'], 200);
        }

        if (blank($user)) {
            return response()->json(['message' => 'User not found'], 200);
        }

        match (true) {
            $this->planSwap($user, $type) => $this->handlePlanSwap($user, $record, $payload),
            $this->renewal($user, $type) => $this->handleRenewal($user, $record, $payload),
            $this->cancellation($user, $type) => $this->handleCancellation($user, $record, $payload),
            $this->refund($user, $type) => $this->handleCancellation($user, $record, $payload),
            $this->paymentFailed($user, $type) => $this->handlePaymentFailure($user, $record, $payload),
            default => null,
        };

        return response()->json(['message' => 'Success'], 200);
    }

    /**
     * Check if this is a duplicate transaction.
     */
    private function isDuplicateTransaction(string $receipt, JVZooTransactionType $type): bool
    {
        return JVZooTransaction::where('receipt', $receipt)
            ->where('transaction_type', $type)
            ->exists();
    }

    /**
     * Get the JVZoo user by email and subscription provider.
     */
    private function getJVZooUser(array $payload): ?User
    {
        return User::where('email', Arr::get($payload, 'ccustemail'))->first();
    }

    /**
     * Determine if this is a first-time user onboarding scenario.
     */
    private function onboardUser(JVZooTransactionType $type, ?User $user): bool
    {
        return $type === JVZooTransactionType::SALE && ! $user;
    }

    /**
     * Determine if this is an existing user plan swap scenario.
     */
    private function planSwap(User $user, JVZooTransactionType $type): bool
    {
        return $type === JVZooTransactionType::SALE && $user->usesJVZoo();
    }

    /**
     * Determine if this is a subscription renewal.
     */
    private function renewal(User $user, JVZooTransactionType $type): bool
    {
        return $type === JVZooTransactionType::BILL && $user->usesJVZoo();
    }

    /**
     * Determine if this is a subscription cancellation.
     */
    private function cancellation(User $user, JVZooTransactionType $type): bool
    {
        return $type === JVZooTransactionType::CANCEL_REBILL && $user->usesJVZoo();
    }

    /**
     * Determine if this is a payment failure.
     */
    private function paymentFailed(User $user, JVZooTransactionType $type): bool
    {
        return $type === JVZooTransactionType::INSF && $user->usesJVZoo();
    }

    /**
     * Determine if this is a refund or chargeback.
     */
    private function refund(User $user, JVZooTransactionType $type): bool
    {
        return $type->isRefund() && $user->usesJVZoo();
    }

    /**
     * Handle onboarding transaction.
     */
    private function handleOnboarding(JVZooTransaction $transaction, array $payload): void
    {
        app(JVZooOnboardingAction::class)->handle($transaction, $payload);
    }

    /**
     * Handle plan swap transaction.
     */
    private function handlePlanSwap(User $user, JVZooTransaction $transaction, array $payload): void
    {
        app(JVZooPlanSwapAction::class)->handle($user, $transaction, $payload);
    }

    /**
     * Handle renewal transaction.
     */
    private function handleRenewal(User $user, JVZooTransaction $transaction, array $payload): void
    {
        $subscription = $user->subscription();

        match (true) {
            $subscription->onTrial() => app(JVZooTrialConversionAction::class)->handle($user, $transaction, $payload),
            default => app(JVZooRenewalAction::class)->handleRenewal($user, $transaction, $payload),
        };
    }

    /**
     * Handle cancellation transaction.
     */
    private function handleCancellation(User $user, JVZooTransaction $transaction, array $payload): void
    {
        app(JVZooCancellationAction::class)->handle($user, $transaction, $payload);
    }

    /**
     * Handle payment failure transaction.
     */
    private function handlePaymentFailure(User $user, JVZooTransaction $transaction, array $payload): void
    {
        app(JVZooPaymentFailureAction::class)->handle($user, $transaction, $payload);
    }
}
