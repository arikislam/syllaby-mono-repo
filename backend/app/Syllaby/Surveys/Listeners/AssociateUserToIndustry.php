<?php

namespace App\Syllaby\Surveys\Listeners;

use Illuminate\Support\Str;
use App\Syllaby\Surveys\Industry;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Surveys\Events\BusinessIntakeFormAnswered;

class AssociateUserToIndustry
{
    /**
     * Handle the event.
     */
    public function handle(BusinessIntakeFormAnswered $event): void
    {
        $industry = Industry::where('slug', Str::slug($event->industry))->first();

        DB::table('industry_user')->insert([
            'industry_id' => $industry->id,
            'user_id' => $event->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
