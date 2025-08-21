<?php

namespace App\Syllaby\Credits\Services;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Credits\CreditEvent;
use App\Syllaby\Credits\CreditHistory;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Events\CreditsRefunded;
use App\Syllaby\Credits\Enums\CreditCalculationTypeEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CreditService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected ?User $user = null)
    {
        $this->user ??= auth()->user();
    }

    /**
     * Set the user for credit operations.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the user credits for the billing period
     */
    public function set(int $amount, CreditEventEnum $type, ?int $total = null, ?string $label = null): void
    {
        if (! $event = $this->getCreditEvent($type)) {
            return;
        }

        $total ??= $amount;

        $this->store(function () use ($event, $amount, $total, $label) {
            $this->recordCreditHistory($event, $amount, balance: $amount, label: $label);
            $this->user->update([
                'monthly_credit_amount' => $total,
                'remaining_credit_amount' => $amount,
            ]);
        });
    }

    /**
     * Adds and records credits history for the given user.
     */
    public function increment(CreditEventEnum $type, ?Model $creditable = null, int $amount = 0, array $meta = []): void
    {
        if (! $event = $this->getCreditEvent($type)) {
            return;
        }

        $balance = $this->useFixedCalculation($event)
            ? $event->value
            : ceil($event->value * $amount);

        $this->store(function () use ($event, $amount, $balance, $creditable, $meta) {
            $this->recordCreditHistory($event, $amount, $balance, $creditable, $meta);
            $this->user->increment('remaining_credit_amount', $balance);
        });
    }

    /**
     * Set the prorated user credits
     */
    public function prorate(int $amount, int $total, CreditEventEnum $type, ?string $label = null): void
    {
        if (! $event = $this->getCreditEvent($type)) {
            return;
        }

        $this->store(function () use ($event, $amount, $total, $label) {
            $this->recordCreditHistory($event, $amount, balance: $amount, label: $label);
            $this->user->update([
                'monthly_credit_amount' => $total,
                'remaining_credit_amount' => $amount,
            ]);
        });
    }

    /**
     * Add and deducts credits history for the given user.
     */
    public function decrement(CreditEventEnum $type, ?Model $creditable = null, int $amount = 0, array $meta = [], ?string $label = null): void
    {
        if (! $credit = $this->getCreditEvent($type)) {
            return;
        }

        $balance = $this->useFixedCalculation($credit)
            ? $credit->value
            : ceil($credit->value * ($amount / 2));

        $this->store(function () use ($credit, $amount, $balance, $creditable, $meta, $label) {
            $this->recordCreditHistory($credit, $amount, $balance, $creditable, $meta, $label);
            $this->user->update($this->deductible($balance));
        });
    }

    /**
     * Refunds user credits.
     */
    public function refund(Model $model, ?CreditEventEnum $refundable = null, array $meta = [], bool $silent = false): void
    {
        if (! $credit = $this->getCreditEvent(CreditEventEnum::REFUNDED_CREDITS)) {
            return;
        }

        $transactions = CreditHistory::where('creditable_id', $model->getKey())
            ->where('creditable_type', $model->getMorphClass())
            ->where('user_id', $this->user->id)
            ->latest('id')
            ->get();

        if ($transactions->isEmpty()) {
            return;
        }

        $transaction = $transactions->first(function ($item) use ($refundable) {
            return blank($refundable) ? $item : $item->description === $refundable->value;
        });

        if (blank($transaction) || $transaction->credit_events_id === $credit->id) {
            return;
        }

        $this->store(function () use ($credit, $transaction, $model, $meta, $silent) {
            $history = $this->recordCreditHistory($credit, $transaction->amount, $transaction->amount, $model, $meta);
            $this->user->increment('remaining_credit_amount', $transaction->amount);
            CreditsRefunded::dispatchUnless($silent, $history);
        });

    }

    /**
     * Apply the purchased extra credits to the user.
     */
    public function applyExtraCredits(User $user, Plan $plan, array $checkout): void
    {
        if (! $event = $this->getCreditEvent(CreditEventEnum::CUSTOM_CREDIT_PURCHASED)) {
            return;
        }

        $this->user = $user;
        $purchasedCredits = (int) Arr::get($plan->meta, 'credits');
        $totalExtraCredits = $this->user->extra_credits + $purchasedCredits;

        $this->store(function () use ($event, $purchasedCredits, $totalExtraCredits) {
            $this->user->update(['extra_credits' => $totalExtraCredits]);
            $this->recordCreditHistory(credit: $event, amount: $purchasedCredits, balance: $purchasedCredits, label: 'Extra Credits Purchased');
        });
    }

    /**
     * Gets the given user credits usage history.
     */
    public function historyFor($user, $paginate = 30, $order = 'DESC'): LengthAwarePaginator
    {
        $query = CreditHistory::query()
            ->where('user_id', $user->id)
            ->with(['creditable', 'user:id,name,email,remaining_credit_amount,monthly_credit_amount',
                'creditEvent:id,type']);

        return $query->orderBy('created_at', $order)->paginate($paginate);
    }

    /**
     * Fetch the credit event with the given name.
     */
    protected function getCreditEvent(CreditEventEnum $event): ?CreditEvent
    {
        if (! $credit = CreditEvent::where('name', $event->value)->first()) {
            return tap(null, fn () => Log::error("Credit event named [{$event->value}] not found"));
        }

        return $credit;
    }

    /**
     * Whether credits will use a fixed or cumulative calculation.
     */
    private function useFixedCalculation(CreditEvent $event): bool
    {
        return $event->calculation_type === CreditCalculationTypeEnum::FIXED->value;
    }

    /**
     * Calculates the remainder credits for the user. If negative defaults to 0;
     */
    private function deductible(int $balance): array
    {
        if ($this->user->remaining_credit_amount >= $balance) {
            return ['remaining_credit_amount' => max($this->user->remaining_credit_amount - $balance, 0)];
        }

        if ($this->user->extra_credits >= $balance) {
            return ['extra_credits' => max($this->user->extra_credits - $balance, 0)];
        }

        $remaining = $balance - $this->user->remaining_credit_amount;

        return [
            'remaining_credit_amount' => 0,
            'extra_credits' => max($this->user->extra_credits - $remaining, 0),
        ];
    }

    /**
     * Saves in storage the user credit usage.
     */
    private function recordCreditHistory(CreditEvent $credit, int $amount, int $balance, ?Model $creditable = null, array $meta = [], ?string $label = null): CreditHistory
    {
        return CreditHistory::create([
            'user_id' => $this->user->id,
            'credit_events_id' => $credit->id,
            'creditable_id' => $creditable?->id,
            'creditable_type' => $creditable?->getMorphClass(),
            'calculative_index' => $amount,
            'event_value' => $credit->value,
            'amount' => $balance,
            'previous_amount' => $this->previousCreditAmount($credit),
            'event_type' => $credit->type,
            'description' => $credit->name,
            'label' => $label,
            'meta' => $meta,
        ]);
    }

    /**
     * Saves the results in storage.
     */
    private function store(callable $callback): void
    {
        try {
            DB::transaction(fn () => $callback(), 4);
        } catch (Throwable $error) {
            Log::error('Failed to add credit', ['response' => $error]);
        }
    }

    /**
     * Calculate the previous credit amount according to given event.
     */
    private function previousCreditAmount(CreditEvent $credit): int
    {
        return match ($credit->name) {
            CreditEventEnum::CUSTOM_CREDIT_PURCHASED->value => $this->user->extra_credits,
            default => $this->user->remaining_credit_amount ?? 0,
        };
    }
}
