<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerOpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'title_fa' => $this->title_fa,
            'title_de' => $this->title_de,
            'description_fa' => $this->description_fa,
            'description_de' => $this->description_de,
            'city' => $this->city,
            'state' => $this->state,
            'training_type' => $this->training_type,
            'start_date' => $this->start_date?->toDateString(),
            'application_deadline' => $this->application_deadline?->toDateString(),
            'monthly_salary_from' => $this->monthly_salary_from,
            'monthly_salary_to' => $this->monthly_salary_to,
            'required_german_level' => $this->required_german_level,
            'education_requirement' => $this->education_requirement,
            'skills' => $this->skills ?? [],
            'accepts_international' => $this->accepts_international,
            'visa_support' => $this->visa_support,
            'contact_email' => $this->contact_email,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'applicants_count' => (int) ($this->applications_count ?? 0),
        ];
    }
}
