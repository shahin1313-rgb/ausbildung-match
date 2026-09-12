<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title_fa' => $this->title_fa,
            'title_de' => $this->title_de,
            'employer_name' => $this->employer_name,
            'description_fa' => $this->description_fa,
            'description_de' => $this->description_de,
            'city' => $this->city,
            'state' => $this->state,
            'training_type' => $this->training_type,
            'start_date' => $this->start_date?->toDateString(),
            'application_deadline' => $this->application_deadline?->toDateString(),
            'monthly_salary_from' => $this->monthly_salary_from,
            'monthly_salary_to' => $this->monthly_salary_to,
            'required_german_level' => strtoupper($this->required_german_level),
            'education_requirement' => $this->education_requirement,
            'skills' => $this->skills ?? [],
            'accepts_international' => $this->accepts_international,
            'visa_support' => $this->visa_support,
            'application_url' => $this->application_url,
            'contact_email' => $this->contact_email,
            'published_at' => $this->published_at?->toIso8601String(),
            'category' => [
                'id' => $this->category->id,
                'slug' => $this->category->slug,
                'name_fa' => $this->category->name_fa,
                'name_de' => $this->category->name_de,
            ],
            'source' => $this->whenLoaded('source', fn (): ?array => $this->source ? [
                'name' => $this->source->name,
                'base_url' => $this->source->base_url,
            ] : null),
            'match_score' => $this->getAttribute('match_score'),
            'match_breakdown' => $this->getAttribute('match_breakdown'),
            'is_favorite' => (bool) $this->getAttribute('is_favorite'),
        ];
    }
}
