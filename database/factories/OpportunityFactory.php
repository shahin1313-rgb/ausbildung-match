<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Opportunity> */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $titleDe = fake()->randomElement([
            'Ausbildung Fachinformatiker/in',
            'Ausbildung Pflegefachfrau/Pflegefachmann',
            'Ausbildung Mechatroniker/in',
            'Ausbildung Kaufmann/-frau',
        ]);

        return [
            'category_id' => Category::factory(),
            'slug' => Str::slug($titleDe).'-'.fake()->unique()->numerify('#####'),
            'title_fa' => fake()->randomElement(['کارآموز فناوری اطلاعات', 'کارآموز پرستاری', 'کارآموز مکاترونیک', 'کارآموز امور اداری']),
            'title_de' => $titleDe,
            'employer_name' => fake()->company(),
            'description_fa' => 'یک فرصت آموزشی ساختاریافته همراه با تجربه عملی، مربی حرفه‌ای و امکان ادامه همکاری.',
            'description_de' => fake()->paragraphs(2, true),
            'city' => fake()->randomElement(['Berlin', 'Hamburg', 'München', 'Köln', 'Leipzig']),
            'state' => fake()->randomElement(['Berlin', 'Hamburg', 'Bayern', 'Nordrhein-Westfalen', 'Sachsen']),
            'training_type' => fake()->randomElement(['dual', 'school']),
            'start_date' => now()->addMonths(fake()->numberBetween(2, 10)),
            'application_deadline' => now()->addDays(fake()->numberBetween(30, 180)),
            'monthly_salary_from' => fake()->numberBetween(950, 1250),
            'monthly_salary_to' => fake()->numberBetween(1300, 1650),
            'required_german_level' => fake()->randomElement(['a2', 'b1', 'b2', 'c1']),
            'education_requirement' => 'حداقل دیپلم یا مدرک معادل',
            'skills' => fake()->randomElements(['Teamarbeit', 'Deutsch', 'MS Office', 'Technik', 'Kommunikation'], 3),
            'accepts_international' => fake()->boolean(75),
            'visa_support' => fake()->randomElement(['unknown', 'no', 'possible', 'yes']),
            'application_url' => fake()->url(),
            'contact_email' => fake()->companyEmail(),
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }
}
