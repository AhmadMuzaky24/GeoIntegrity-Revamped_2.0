<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['A', 'B', 'C'] as $suffix) {
            User::firstOrCreate(
                ['email' => strtolower($suffix).'@demo.test'],
                [
                    'name' => 'User '.$suffix,
                    'password' => Hash::make('demo'),
                ],
            );
        }
    }
}
