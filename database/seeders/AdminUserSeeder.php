<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [];

        if (! app()->environment('production') && config('seeding.demo_enabled')) {
            $accounts[] = ['name' => 'مدیر آزمایشی', 'email' => 'admin@test.com', 'password' => 'password'];
        }

        if (config('seeding.admin_enabled')) {
            $email = (string) config('seeding.admin_email');
            $password = (string) config('seeding.admin_password');

            if ($email === '' || $password === '' || $email === 'admin@example.com' || $password === 'ChangeMe123!' || strlen($password) < 12) {
                throw new LogicException('ADMIN_EMAIL and a non-placeholder ADMIN_PASSWORD of at least 12 characters are required.');
            }

            $accounts[] = [
                'name' => config('seeding.admin_name'),
                'email' => $email,
                'password' => $password,
            ];
        }

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'is_admin' => true,
                    'password' => Hash::make($account['password']),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
