<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GermanCvUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'contact' => ['sometimes', 'array'],
            'contact.email' => ['nullable', 'email:rfc', 'max:190'],
            'contact.phone' => ['nullable', 'string', 'max:40'],
            'contact.city' => ['nullable', 'string', 'max:100'],
            'experiences' => ['sometimes', 'array', 'max:20'],
            'experiences.*.title' => ['required_with:experiences', 'string', 'max:190'],
            'experiences.*.company' => ['nullable', 'string', 'max:190'],
            'experiences.*.period' => ['nullable', 'string', 'max:100'],
            'experiences.*.description' => ['nullable', 'string', 'max:1000'],
            'education' => ['sometimes', 'array', 'max:20'],
            'education.*.title' => ['required_with:education', 'string', 'max:190'],
            'education.*.school' => ['nullable', 'string', 'max:190'],
            'education.*.period' => ['nullable', 'string', 'max:100'],
            'skills' => ['sometimes', 'array', 'max:40'],
            'skills.*' => ['string', 'max:80'],
            'languages' => ['sometimes', 'array', 'max:20'],
            'languages.*.name' => ['required_with:languages', 'string', 'max:80'],
            'languages.*.level' => ['nullable', 'string', 'max:40'],
            'certificates' => ['sometimes', 'array', 'max:20'],
            'certificates.*' => ['string', 'max:190'],
        ];
    }
}
