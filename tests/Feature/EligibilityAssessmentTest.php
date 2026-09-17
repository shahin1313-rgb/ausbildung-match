<?php

namespace Tests\Feature;

use Tests\TestCase;

class EligibilityAssessmentTest extends TestCase
{
    public function test_a_visitor_can_receive_a_real_eligibility_assessment(): void
    {
        $payload = [
            'age' => 27,
            'education_level' => 'diploma',
            'german_level' => 'b1',
            'work_experience_years' => 1,
        ];

        $this->postJson('/api/v1/eligibility/assess', $payload)
            ->assertOk()
            ->assertJsonPath('assessment.score', 80)
            ->assertJsonPath('assessment.level.code', 'good')
            ->assertJsonPath('assessment.readiness.code', 'ready')
            ->assertJsonPath('assessment.readiness.can_start', true)
            ->assertJsonStructure([
                'assessment' => [
                    'score', 'level', 'readiness', 'breakdown', 'strengths', 'missing',
                    'improvements', 'explanation', 'disclaimer',
                ],
            ]);
    }

    public function test_invalid_assessment_answers_are_rejected(): void
    {
        $this->postJson('/api/v1/eligibility/assess', [
            'age' => 8,
            'education_level' => 'invalid',
            'german_level' => 'z9',
            'work_experience_years' => -1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'age', 'education_level', 'german_level', 'work_experience_years',
            ]);
    }
}
