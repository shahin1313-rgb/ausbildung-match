<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployerOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'category_id' => [$required, 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'title_fa' => [$required, 'string', 'max:255'],
            'title_de' => [$required, 'string', 'max:255'],
            'description_fa' => [$required, 'string', 'max:20000'],
            'description_de' => ['nullable', 'string', 'max:20000'],
            'city' => [$required, 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'training_type' => [$required, Rule::in(['dual', 'school'])],
            'start_date' => ['nullable', 'date'],
            'application_deadline' => [
                'nullable',
                'date',
                Rule::when($this->input('status') === 'published', ['after_or_equal:today']),
            ],
            'monthly_salary_from' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'monthly_salary_to' => ['nullable', 'integer', 'min:0', 'max:100000', 'gte:monthly_salary_from'],
            'required_german_level' => [$required, Rule::in(['a2', 'b1', 'b2', 'c1'])],
            'education_requirement' => ['nullable', 'string', 'max:500'],
            'skills' => ['nullable', 'array', 'max:30'],
            'skills.*' => ['string', 'max:100'],
            'accepts_international' => ['sometimes', 'boolean'],
            'visa_support' => ['sometimes', Rule::in(['unknown', 'no', 'possible', 'yes'])],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'status' => [$required, Rule::in(['draft', 'published', 'expired'])],
        ];
    }
}
