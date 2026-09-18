<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'آدرس ایمیل را وارد کنید.',
            'email.email' => 'آدرس ایمیل معتبر نیست؛ نمونه صحیح: name@example.com',
            'email.max' => 'آدرس ایمیل بیش از حد طولانی است.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.string' => 'رمز عبور واردشده معتبر نیست.',
            'password.max' => 'رمز عبور واردشده بیش از حد طولانی است.',
            'remember.boolean' => 'مقدار گزینه «مرا به خاطر بسپار» معتبر نیست.',
        ];
    }
}
