<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_current_published_opportunities_are_listed(): void
    {
        $category = Category::query()->create([
            'name_fa' => 'فناوری اطلاعات',
            'name_de' => 'IT',
            'slug' => 'it',
            'is_active' => true,
        ]);

        $base = [
            'category_id' => $category->id,
            'title_fa' => 'توسعه نرم‌افزار',
            'title_de' => 'Fachinformatiker Anwendungsentwicklung',
            'employer_name' => 'Test GmbH',
            'description_fa' => 'توضیحات فرصت آزمایشی',
            'city' => 'Berlin',
            'application_url' => 'https://example.com/apply',
            'published_at' => now(),
        ];

        Opportunity::query()->create([...$base, 'status' => 'published', 'application_deadline' => now()->addMonth()]);
        Opportunity::query()->create([...$base, 'title_de' => 'Expired Ausbildung', 'status' => 'published', 'application_deadline' => now()->subDay()]);
        Opportunity::query()->create([...$base, 'title_de' => 'Draft Ausbildung', 'status' => 'draft']);

        $this->getJson('/api/v1/opportunities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city', 'Berlin');
    }
}
