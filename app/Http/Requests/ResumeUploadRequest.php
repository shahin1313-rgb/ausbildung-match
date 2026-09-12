<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ResumeUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resume' => [
                'required',
                File::types(['pdf', 'doc', 'docx'])->max(5 * 1024),
            ],
        ];
    }
}
