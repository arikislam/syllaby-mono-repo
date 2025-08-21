<?php

namespace Database\Factories;

use RRule\RRule;
use App\Syllaby\Users\User;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchedulerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Scheduler::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'idea_id' => null,
            'title' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'topic' => fake()->sentence(),
            'status' => fake()->randomElement(SchedulerStatus::values()),
            'source' => fake()->randomElement(SchedulerSource::values()),
            'type' => 'faceless',
            'options' => null,
            'rrules' => $this->rules(),
            'metadata' => null,
            'paused_at' => null,
        ];
    }

    private function rules(): array
    {
        $rule = new RRule([
            'COUNT' => 1,
            'INTERVAL' => 1,
            'FREQ' => RRule::DAILY,
            'DTSTART' => now()->startOfDay(),
        ]);

        return [$rule->rfcString()];
    }
}
