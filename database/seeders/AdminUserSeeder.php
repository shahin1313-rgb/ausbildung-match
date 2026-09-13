<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'مدیر آزمایشی', 'email' => 'admin@test.com', 'is_admin' => true],
            ['name' => env('ADMIN_NAME', 'مدیر سایت'), 'email' => env('ADMIN_EMAIL', 'admin@example.com'), 'is_admin' => true],
        ];

        foreach ($accounts as $account) {
            $password = $account['email'] === 'admin@test.com' ? 'password' : env('ADMIN_PASSWORD', 'ChangeMe123!');
            User::query()->updateOrCreate(['email' => $account['email']], [...$account, 'password' => Hash::make($password), 'email_verified_at' => now()]);
        }
    }
}
