<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_complete_and_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('opportunities', 60);
        $this->assertSame(20, User::query()->where('email', 'like', 'user%@test.com')->count());
        $this->assertSame(10, User::query()->whereIn('email', array_map(fn (int $i) => "user{$i}@test.com", range(1, 10)))->count());
        $this->assertSame(5, User::query()->where('email', 'like', 'employer%@test.com')->count());
        $this->assertDatabaseCount('user_profiles', 20);
        $this->assertDatabaseCount('german_cvs', 15);
        $this->assertDatabaseCount('resumes', 12);
        $this->assertDatabaseCount('favorites', 40);
        $this->assertDatabaseCount('application_clicks', 30);
        $this->assertSame(15, Opportunity::query()->distinct()->count('employer_name'));

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('password', $admin->password));

        foreach (range(1, 10) as $number) {
            $user = User::query()->where('email', "user{$number}@test.com")->firstOrFail();
            $this->assertFalse($user->is_admin);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check('password', $user->password));
        }
    }
}
