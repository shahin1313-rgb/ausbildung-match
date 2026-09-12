<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\UserProfile;

class OpportunityMatcher
{
    private const LANGUAGE_RANK = [
        'none' => 0,
        'a1' => 1,
        'a2' => 2,
        'b1' => 3,
        'b2' => 4,
        'c1' => 5,
        'c2' => 6,
    ];

    public function score(Opportunity $opportunity, UserProfile $profile): array
    {
        $breakdown = [
            'language' => $this->languageScore($opportunity, $profile),
            'education' => $this->educationScore($opportunity, $profile),
            'skills' => $this->skillsScore($opportunity, $profile),
            'experience' => min(10, $profile->work_experience_years * 5),
            'international' => $opportunity->accepts_international ? 10 : 0,
            'mobility' => $this->mobilityScore($opportunity, $profile),
            'availability' => $this->availabilityScore($opportunity, $profile),
            'interest' => in_array($opportunity->category_id, $profile->preferred_category_ids ?? [], true) ? 5 : 0,
        ];

        return [
            'score' => (int) round(array_sum($breakdown)),
            'breakdown' => $breakdown,
        ];
    }

    private function languageScore(Opportunity $opportunity, UserProfile $profile): int
    {
        $user = self::LANGUAGE_RANK[$profile->german_level] ?? 0;
        $required = self::LANGUAGE_RANK[$opportunity->required_german_level] ?? 3;
        $difference = $user - $required;

        return match (true) {
            $difference >= 0 => 25,
            $difference === -1 => 12,
            default => 0,
        };
    }

    private function educationScore(Opportunity $opportunity, UserProfile $profile): int
    {
        if (! $profile->education_level) {
            return 0;
        }

        if (! $opportunity->education_requirement) {
            return 20;
        }

        return $profile->education_title ? 20 : 14;
    }

    private function skillsScore(Opportunity $opportunity, UserProfile $profile): int
    {
        $required = collect($opportunity->skills ?? [])
            ->map(fn (string $skill): string => mb_strtolower(trim($skill)))
            ->filter()
            ->unique();

        if ($required->isEmpty()) {
            return 20;
        }

        $userSkills = collect($profile->skills ?? [])
            ->map(fn (string $skill): string => mb_strtolower(trim($skill)))
            ->filter()
            ->unique();

        $matched = $required->intersect($userSkills)->count();

        return (int) round(($matched / $required->count()) * 20);
    }

    private function mobilityScore(Opportunity $opportunity, UserProfile $profile): int
    {
        if ($profile->relocation_ready) {
            return 5;
        }

        return in_array($opportunity->city, $profile->preferred_cities ?? [], true) ? 5 : 0;
    }

    private function availabilityScore(Opportunity $opportunity, UserProfile $profile): int
    {
        if (! $opportunity->start_date || ! $profile->available_from) {
            return 3;
        }

        return $profile->available_from->lte($opportunity->start_date) ? 5 : 0;
    }
}
