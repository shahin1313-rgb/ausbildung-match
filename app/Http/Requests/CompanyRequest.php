<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'website' => ['nullable', 'url', 'max:500'],
            'contact_email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
