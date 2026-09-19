<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerApplicantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sharedResume = $this->resume;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'applied_at' => $this->applied_at?->toIso8601String(),
            'interview_at' => $this->interview_at?->toIso8601String(),
            'candidate_message' => $this->candidate_message,
            'candidate' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->profile?->phone,
                'country' => $this->user->profile?->country,
                'german_level' => $this->user->profile?->german_level,
                'education_title' => $this->user->profile?->education_title,
                'skills' => $this->user->profile?->skills ?? [],
            ],
            'resume' => $sharedResume ? [
                'original_name' => $sharedResume->original_name,
                'size_bytes' => $sharedResume->size_bytes,
                'download_url' => route('employer.applications.resume', $this->id, absolute: false),
            ] : null,
        ];
    }
}
