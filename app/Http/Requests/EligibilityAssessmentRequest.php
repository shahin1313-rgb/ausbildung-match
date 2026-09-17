<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EligibilityAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'age' => ['required', 'integer', 'min:14', 'max:65'],
            'education_level' => ['required', Rule::in(['below_diploma', 'diploma', 'associate', 'bachelor', 'master', 'doctorate'])],
            'german_level' => ['required', Rule::in(['none', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'])],
            'work_experience_years' => ['required', 'integer', 'min:0', 'max:50'],
        ];
    }
}
