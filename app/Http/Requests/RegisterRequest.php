<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'accept_terms' => ['required', 'accepted'],
            'accept_privacy' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام و نام خانوادگی را وارد کنید.',
            'name.string' => 'نام و نام خانوادگی باید به‌صورت متن وارد شود.',
            'name.max' => 'نام و نام خانوادگی نباید بیشتر از ۱۲۰ نویسه باشد.',
            'email.required' => 'آدرس ایمیل را وارد کنید.',
            'email.email' => 'آدرس ایمیل معتبر نیست؛ نمونه صحیح: name@example.com',
            'email.max' => 'آدرس ایمیل بیش از حد طولانی است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است. وارد حساب شوید یا بازیابی رمز عبور را انتخاب کنید.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.confirmed' => 'رمز عبور و تکرار آن یکسان نیستند.',
            'password.min' => 'رمز عبور باید حداقل ۸ نویسه باشد.',
            'password.letters' => 'رمز عبور باید حداقل یک حرف داشته باشد.',
            'password.numbers' => 'رمز عبور باید حداقل یک عدد داشته باشد.',
            'accept_terms.required' => 'برای ساخت حساب باید شرایط استفاده را بپذیرید.',
            'accept_terms.accepted' => 'برای ساخت حساب باید شرایط استفاده را بپذیرید.',
            'accept_privacy.required' => 'برای ساخت حساب باید سیاست حریم خصوصی را تأیید کنید.',
            'accept_privacy.accepted' => 'برای ساخت حساب باید سیاست حریم خصوصی را تأیید کنید.',
        ];
    }
}
