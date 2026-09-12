<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'german_level' => ['required', Rule::in(['none', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'])],
            'education_level' => ['nullable', Rule::in(['below_diploma', 'diploma', 'associate', 'bachelor', 'master', 'doctorate'])],
            'education_title' => ['nullable', 'string', 'max:190'],
            'skills' => ['sometimes', 'array', 'max:30'],
            'skills.*' => ['string', 'max:80'],
            'preferred_category_ids' => ['sometimes', 'array', 'max:10'],
            'preferred_category_ids.*' => ['integer', 'exists:categories,id'],
            'preferred_cities' => ['sometimes', 'array', 'max:20'],
            'preferred_cities.*' => ['string', 'max:100'],
            'work_experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'relocation_ready' => ['required', 'boolean'],
            'available_from' => ['nullable', 'date'],
        ];
    }
}
