<?php

namespace Tests\Unit;

use App\Services\EligibilityAssessmentService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EligibilityAssessmentServiceTest extends TestCase
{
    #[DataProvider('assessmentCases')]
    public function test_it_returns_a_repeatable_readiness_assessment(
        array $input,
        int $expectedScore,
        string $expectedLevel,
        string $expectedReadiness,
    ): void {
        $result = app(EligibilityAssessmentService::class)->assess($input);

        $this->assertSame($expectedScore, $result['score']);
        $this->assertSame($expectedLevel, $result['level']['code']);
        $this->assertSame($expectedReadiness, $result['readiness']['code']);
        $this->assertSame(100, collect($result['breakdown'])->sum('max'));
        $this->assertNotEmpty($result['disclaimer']);
    }

    public static function assessmentCases(): array
    {
        return [
            'ready profile' => [
                ['age' => 24, 'education_level' => 'bachelor', 'german_level' => 'b2', 'work_experience_years' => 2],
                97,
                'very_good',
                'ready',
            ],
            'profile needing preparation' => [
                ['age' => 46, 'education_level' => 'below_diploma', 'german_level' => 'a1', 'work_experience_years' => 0],
                27,
                'weak',
                'preparation',
            ],
        ];
    }
}
