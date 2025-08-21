<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Credits\CreditEvent;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Credits\Enums\CreditEventTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CreditHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'credit_events_id' => CreditEvent::inRandomOrder()->first()->id,
            'creditable_type' => fake()->randomElement(['keyword', 'video', 'speech', 'generator']),
            'creditable_id' => mt_rand(),
            'description' => fn (array $attr) => CreditEvent::find($attr['credit_events_id'])->first()->name,
            'calculative_index' => fake()->randomNumber(4),
            'event_value' => fake()->randomNumber(4),
            'amount' => fake()->randomNumber(4),
            'previous_amount' => fake()->randomNumber(4),
            'event_type' => fake()->randomElement([CreditEventTypeEnum::ADDED, CreditEventTypeEnum::SPEND]),
            'meta' => fake()->randomElement([null, ['key' => 'value']]),
        ];
    }

    public function of(CreditEvent $event): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_events_id' => $event->id,
            'description' => $event->name,
            'event_value' => $event->value,
            'event_type' => $event->type,
        ]);
    }
}
