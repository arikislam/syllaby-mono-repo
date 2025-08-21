<?php

namespace Database\Seeders;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Syllaby\Users\Enums\UserType;

class SystemUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'system@syllaby.io',
            'user_type' => UserType::ADMIN,
        ], [
            'name' => 'System Admin',
            'email_verified_at' => now(),
            'password' => Str::random(),
        ]);
    }
}
