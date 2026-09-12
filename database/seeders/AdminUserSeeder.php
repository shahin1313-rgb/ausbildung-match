<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'مدیر سایت'),
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->profile()->firstOrCreate([], [
            'german_level' => 'b2',
            'work_experience_years' => 1,
            'relocation_ready' => true,
        ]);
    }
}
