<?php

namespace Tests\Unit;

use App\Models\Opportunity;
use App\Models\UserProfile;
use App\Services\OpportunityMatcher;
use Tests\TestCase;

class OpportunityMatcherTest extends TestCase
{
    public function test_it_gives_a_high_score_to_a_matching_profile(): void
    {
        $opportunity = new Opportunity([
            'category_id' => 10,
            'required_german_level' => 'b1',
            'education_requirement' => 'Diploma',
            'skills' => ['PHP', 'Git'],
            'accepts_international' => true,
            'city' => 'Berlin',
            'start_date' => now()->addMonths(4),
        ]);

        $profile = new UserProfile([
            'german_level' => 'b2',
            'education_level' => 'diploma',
            'education_title' => 'Computer Science',
            'skills' => ['PHP', 'Git'],
            'preferred_category_ids' => [10],
            'preferred_cities' => ['Berlin'],
            'work_experience_years' => 2,
            'relocation_ready' => false,
            'available_from' => now()->addMonth(),
        ]);

        $result = app(OpportunityMatcher::class)->score($opportunity, $profile);

        $this->assertSame(100, $result['score']);
    }
}
