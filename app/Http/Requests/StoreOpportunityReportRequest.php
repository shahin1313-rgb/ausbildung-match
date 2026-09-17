<?php

namespace App\Http\Requests;

use App\Models\OpportunityReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'details' => is_string($this->input('details')) ? trim($this->input('details')) : $this->input('details'),
            'reporter_email' => is_string($this->input('reporter_email'))
                ? strtolower(trim($this->input('reporter_email')))
                : $this->input('reporter_email'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(OpportunityReport::REASONS)],
            'details' => [
                Rule::requiredIf(in_array($this->input('reason'), ['scam', 'incorrect_info', 'other'], true)),
                'nullable',
                'string',
                'min:10',
                'max:2000',
            ],
            'reporter_email' => [
                Rule::requiredIf(! $this->user()),
                'nullable',
                'email:rfc',
                'max:255',
            ],
            // Hidden honeypot field. Normal clients leave it empty.
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'نوع مشکل را انتخاب کنید.',
            'reason.in' => 'نوع گزارش انتخاب‌شده معتبر نیست.',
            'details.required' => 'لطفاً جزئیات لازم برای بررسی گزارش را بنویسید.',
            'details.min' => 'جزئیات گزارش باید حداقل ۱۰ نویسه باشد.',
            'details.max' => 'جزئیات گزارش نمی‌تواند بیشتر از ۲۰۰۰ نویسه باشد.',
            'reporter_email.required' => 'برای پیگیری گزارش، ایمیل خود را وارد کنید.',
            'reporter_email.email' => 'آدرس ایمیل معتبر نیست.',
            'website.max' => 'ارسال گزارش ممکن نشد.',
        ];
    }
}
