<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->company(),
            'legal_name' => fake()->company(),
            'website' => fake()->url(),
            'contact_email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->randomElement(['Berlin', 'Hamburg', 'München', 'Köln']),
            'address' => fake()->address(),
            'description' => fake()->paragraph(),
            'status' => 'verified',
            'verification_method' => 'admin_review',
            'verified_at' => now(),
        ];
    }
}
