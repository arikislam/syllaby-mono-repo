<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Syllaby\Credits\CreditEvent;

class CreditEventTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('credit-engine.events') as $name => $event) {
            CreditEvent::updateOrCreate(['name' => $name], $event);
        }
    }
}
